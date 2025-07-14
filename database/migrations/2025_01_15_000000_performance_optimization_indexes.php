<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * PERFORMANCE OPTIMIZATION INDEXES
     * Add all missing indexes for optimal query performance
     */
    public function up(): void
    {
        // 🔥 CRITICAL PERFORMANCE INDEXES

        // Questions table - Most critical for quiz performance
        Schema::table('questions', function (Blueprint $table) {
            // Covering index for quiz generation (most important)
            if (!DB::select("SHOW INDEX FROM questions WHERE Key_name = 'questions_quiz_generation_idx'")) {
                $table->index(['topic_id', 'source', 'question_type', 'deleted'], 'questions_quiz_generation_idx');
            }
            
            // Index for question retrieval
            if (!DB::select("SHOW INDEX FROM questions WHERE Key_name = 'questions_topic_source_idx'")) {
                $table->index(['topic_id', 'source'], 'questions_topic_source_idx');
            }
            
            // Index for question type filtering
            if (!DB::select("SHOW INDEX FROM questions WHERE Key_name = 'questions_type_idx'")) {
                $table->index(['question_type', 'deleted'], 'questions_type_idx');
            }
        });

        // Quiz attempts table - Critical for performance tracking
        Schema::table('quiz_attempts', function (Blueprint $table) {
            // Covering index for user performance analysis
            if (!DB::select("SHOW INDEX FROM quiz_attempts WHERE Key_name = 'quiz_attempts_user_performance_idx'")) {
                $table->index(['user_id', 'topic_id', 'percentage_grade', 'created_at'], 'quiz_attempts_user_performance_idx');
            }
            
            // Index for recent attempts
            if (!DB::select("SHOW INDEX FROM quiz_attempts WHERE Key_name = 'quiz_attempts_user_created_idx'")) {
                $table->index(['user_id', 'created_at'], 'quiz_attempts_user_created_idx');
            }
            
            // Index for topic statistics
            if (!DB::select("SHOW INDEX FROM quiz_attempts WHERE Key_name = 'quiz_attempts_topic_stats_idx'")) {
                $table->index(['topic_id', 'percentage_grade'], 'quiz_attempts_topic_stats_idx');
            }
        });

        // Mistakes table - Critical for mistake review performance
        Schema::table('mistakes', function (Blueprint $table) {
            // Covering index for user mistake review
            if (!DB::select("SHOW INDEX FROM mistakes WHERE Key_name = 'mistakes_user_review_idx'")) {
                $table->index(['user_id', 'mastered', 'last_attempt_date'], 'mistakes_user_review_idx');
            }
            
            // Index for question difficulty analysis
            if (!DB::select("SHOW INDEX FROM mistakes WHERE Key_name = 'mistakes_question_difficulty_idx'")) {
                $table->index(['question_id', 'mastered'], 'mistakes_question_difficulty_idx');
            }
            
            // Index for user mistake tracking
            if (!DB::select("SHOW INDEX FROM mistakes WHERE Key_name = 'mistakes_user_date_idx'")) {
                $table->index(['user_id', 'last_attempt_date'], 'mistakes_user_date_idx');
            }
        });

        // Users table - Critical for user management
        Schema::table('users', function (Blueprint $table) {
            // Covering index for user stats
            if (!DB::select("SHOW INDEX FROM users WHERE Key_name = 'users_stats_idx'")) {
                $table->index(['id', 'xp', 'level', 'total_questions_attempted'], 'users_stats_idx');
            }
            
            // Index for active users
            if (!DB::select("SHOW INDEX FROM users WHERE Key_name = 'users_active_idx'")) {
                $table->index(['last_active_at', 'is_admin'], 'users_active_idx');
            }
        });

        // Topics table - Critical for course navigation
        Schema::table('topics', function (Blueprint $table) {
            // Covering index for course topic listing
            if (!DB::select("SHOW INDEX FROM topics WHERE Key_name = 'topics_course_listing_idx'")) {
                $table->index(['course_id', 'display_order', 'name'], 'topics_course_listing_idx');
            }
            
            // Index for topic search
            if (!DB::select("SHOW INDEX FROM topics WHERE Key_name = 'topics_name_idx'")) {
                $table->index(['name'], 'topics_name_idx');
            }
        });

        // Course_user table - Critical for enrollment checks
        Schema::table('course_user', function (Blueprint $table) {
            // Covering index for enrollment checks
            if (!DB::select("SHOW INDEX FROM course_user WHERE Key_name = 'course_user_enrollment_idx'")) {
                $table->index(['user_id', 'course_id', 'enrollment_status'], 'course_user_enrollment_idx');
            }
            
            // Index for course access validation
            if (!DB::select("SHOW INDEX FROM course_user WHERE Key_name = 'course_user_access_idx'")) {
                $table->index(['course_id', 'enrollment_status'], 'course_user_access_idx');
            }
        });

        // Notifications table - Critical for notification performance
        Schema::table('notifications', function (Blueprint $table) {
            // Covering index for user notifications
            if (!DB::select("SHOW INDEX FROM notifications WHERE Key_name = 'notifications_user_idx'")) {
                $table->index(['user_id', 'is_read', 'created_at'], 'notifications_user_idx');
            }
            
            // Index for notification types
            if (!DB::select("SHOW INDEX FROM notifications WHERE Key_name = 'notifications_type_idx'")) {
                $table->index(['type', 'created_at'], 'notifications_type_idx');
            }
        });

        // Payments table - Critical for payment processing
        Schema::table('payments', function (Blueprint $table) {
            // Covering index for payment status tracking
            if (!DB::select("SHOW INDEX FROM payments WHERE Key_name = 'payments_status_idx'")) {
                $table->index(['status', 'created_at', 'user_id'], 'payments_status_idx');
            }
            
            // Index for payment verification
            if (!DB::select("SHOW INDEX FROM payments WHERE Key_name = 'payments_reference_idx'")) {
                $table->index(['reference_number'], 'payments_reference_idx');
            }
        });

        // User_topic_progress table - Critical for progress tracking
        Schema::table('user_topic_progress', function (Blueprint $table) {
            // Covering index for user progress
            // if (!DB::select("SHOW INDEX FROM user_topic_progress WHERE Key_name = 'user_topic_progress_idx'")) {
            //     // COMMENTED OUT: study_mode column does not exist
            //     // $table->index(['user_id', 'topic_id', 'study_mode'], 'user_topic_progress_idx');
            // }
        });

        // 🔥 COMPOSITE INDEXES FOR COMPLEX QUERIES

        // Questions with alternative answers
        Schema::table('questions', function (Blueprint $table) {
            // if (!DB::select("SHOW INDEX FROM questions WHERE Key_name = 'questions_alt_answers_idx'")) {
            //     // COMMENTED OUT: Cannot index JSON column directly in MySQL
            //     // $table->index(['topic_id', 'question_type', 'alternative_answers'], 'questions_alt_answers_idx');
            // }
        });

        // Quiz attempts with duration
        Schema::table('quiz_attempts', function (Blueprint $table) {
            // if (!DB::select("SHOW INDEX FROM quiz_attempts WHERE Key_name = 'quiz_attempts_duration_idx'")) {
            //     // COMMENTED OUT: duration column does not exist
            //     // $table->index(['user_id', 'duration', 'percentage_grade'], 'quiz_attempts_duration_idx');
            // }
        });

        // 🔥 FULL-TEXT SEARCH INDEXES

        // Questions full-text search
        Schema::table('questions', function (Blueprint $table) {
            if (!DB::select("SHOW INDEX FROM questions WHERE Key_name = 'questions_search_idx'")) {
                $table->fullText(['question', 'explanation'], 'questions_search_idx');
            }
        });

        // Topics full-text search
        Schema::table('topics', function (Blueprint $table) {
            if (!DB::select("SHOW INDEX FROM topics WHERE Key_name = 'topics_search_idx'")) {
                $table->fullText(['name', 'description'], 'topics_search_idx');
            }
        });

        // 🔥 PARTIAL INDEXES (MySQL 8.0+)

        try {
            // Active questions only
            DB::statement("CREATE INDEX questions_active_idx ON questions(topic_id, source) WHERE deleted = 0");
        } catch (\Exception $e) {
            // Partial indexes not supported, skip
        }

        try {
            // Recent quiz attempts only
            DB::statement("CREATE INDEX quiz_attempts_recent_idx ON quiz_attempts(user_id, topic_id) WHERE created_at > DATE_SUB(NOW(), INTERVAL 90 DAY)");
        } catch (\Exception $e) {
            // Partial indexes not supported, skip
        }

        try {
            // Unread notifications only
            DB::statement("CREATE INDEX notifications_unread_idx ON notifications(user_id, created_at) WHERE is_read = 0");
        } catch (\Exception $e) {
            // Partial indexes not supported, skip
        }

        // 🔥 ANALYZE TABLES FOR OPTIMIZATION
        $tables = ['questions', 'quiz_attempts', 'mistakes', 'users', 'topics', 'course_user', 'notifications', 'payments'];
        
        foreach ($tables as $table) {
            try {
                DB::statement("ANALYZE TABLE {$table}");
            } catch (\Exception $e) {
                // Skip if table doesn't exist or other issues
            }
        }

        Log::info('Performance optimization indexes created successfully', [
            'timestamp' => now(),
            'tables_optimized' => count($tables)
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop partial indexes
        try {
            DB::statement("DROP INDEX questions_active_idx ON questions");
            DB::statement("DROP INDEX quiz_attempts_recent_idx ON quiz_attempts");
            DB::statement("DROP INDEX notifications_unread_idx ON notifications");
        } catch (\Exception $e) {
            // Indexes may not exist
        }

        // Drop full-text indexes
        Schema::table('topics', function (Blueprint $table) {
            $table->dropFullText('topics_search_idx');
        });

        Schema::table('questions', function (Blueprint $table) {
            $table->dropFullText('questions_search_idx');
        });

        // Drop composite indexes
        Schema::table('user_topic_progress', function (Blueprint $table) {
            $table->dropIndex('user_topic_progress_idx');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex('payments_reference_idx');
            $table->dropIndex('payments_status_idx');
        });

        Schema::table('notifications', function (Blueprint $table) {
            $table->dropIndex('notifications_type_idx');
            $table->dropIndex('notifications_user_idx');
        });

        Schema::table('course_user', function (Blueprint $table) {
            $table->dropIndex('course_user_access_idx');
            $table->dropIndex('course_user_enrollment_idx');
        });

        Schema::table('topics', function (Blueprint $table) {
            $table->dropIndex('topics_name_idx');
            $table->dropIndex('topics_course_listing_idx');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('users_active_idx');
            $table->dropIndex('users_stats_idx');
        });

        Schema::table('mistakes', function (Blueprint $table) {
            $table->dropIndex('mistakes_user_date_idx');
            $table->dropIndex('mistakes_question_difficulty_idx');
            $table->dropIndex('mistakes_user_review_idx');
        });

        Schema::table('quiz_attempts', function (Blueprint $table) {
            $table->dropIndex('quiz_attempts_topic_stats_idx');
            $table->dropIndex('quiz_attempts_user_created_idx');
            $table->dropIndex('quiz_attempts_user_performance_idx');
        });

        Schema::table('questions', function (Blueprint $table) {
            $table->dropIndex('questions_type_idx');
            $table->dropIndex('questions_topic_source_idx');
            $table->dropIndex('questions_quiz_generation_idx');
        });
    }
};