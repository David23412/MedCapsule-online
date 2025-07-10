<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * BRUTAL DATABASE OPTIMIZATIONS - NO MERCY EDITION
     * Implements the most aggressive performance optimizations possible
     */
    public function up(): void
    {
        // 🔥 BRUTALLY AGGRESSIVE INDEX OPTIMIZATIONS
        
        // COVERING INDEXES - Maximum query speed with zero table lookups
        Schema::table('users', function (Blueprint $table) {
            // Covering index for authentication queries
            if (!DB::select("SHOW INDEX FROM users WHERE Key_name = 'users_auth_covering_brutal_idx'")) {
                $table->index(['email', 'password', 'email_verified_at', 'is_admin'], 'users_auth_covering_brutal_idx');
            }
            
            // Covering index for user stats
            if (!DB::select("SHOW INDEX FROM users WHERE Key_name = 'users_stats_covering_brutal_idx'")) {
                $table->index(['id', 'correct_answers_count', 'total_questions_attempted', 'xp', 'study_streak_days'], 'users_stats_covering_brutal_idx');
            }
        });

        // Quiz attempts - MAXIMUM PERFORMANCE covering indexes
        Schema::table('quiz_attempts', function (Blueprint $table) {
            // Covering index for user performance analysis
            if (!DB::select("SHOW INDEX FROM quiz_attempts WHERE Key_name = 'quiz_attempts_performance_brutal_idx'")) {
                $table->index(['user_id', 'topic_id', 'percentage_grade', 'correct_answers', 'total_questions', 'created_at'], 'quiz_attempts_performance_brutal_idx');
            }
            
            // Covering index for topic statistics
            if (!DB::select("SHOW INDEX FROM quiz_attempts WHERE Key_name = 'quiz_attempts_topic_stats_brutal_idx'")) {
                $table->index(['topic_id', 'percentage_grade', 'created_at', 'user_id'], 'quiz_attempts_topic_stats_brutal_idx');
            }
        });

        // Questions - BRUTAL query optimization
        Schema::table('questions', function (Blueprint $table) {
            // Covering index for quiz generation
            if (!DB::select("SHOW INDEX FROM questions WHERE Key_name = 'questions_quiz_generation_brutal_idx'")) {
                $table->index(['topic_id', 'source', 'question_type', 'deleted', 'id', 'difficulty'], 'questions_quiz_generation_brutal_idx');
            }
            
            // Covering index for question retrieval with all metadata
            if (!DB::select("SHOW INDEX FROM questions WHERE Key_name = 'questions_full_covering_brutal_idx'")) {
                $table->index(['id', 'topic_id', 'question_type', 'source', 'deleted', 'created_at'], 'questions_full_covering_brutal_idx');
            }
        });

        // Topics - EXTREME performance indexes
        Schema::table('topics', function (Blueprint $table) {
            // Covering index for course topic listing
            if (!DB::select("SHOW INDEX FROM topics WHERE Key_name = 'topics_course_listing_brutal_idx'")) {
                $table->index(['course_id', 'display_order', 'case_type', 'name', 'id'], 'topics_course_listing_brutal_idx');
            }
        });

        // Mistakes - BRUTAL mistake analysis indexes
        Schema::table('mistakes', function (Blueprint $table) {
            // Covering index for user mistake review
            if (!DB::select("SHOW INDEX FROM mistakes WHERE Key_name = 'mistakes_review_brutal_idx'")) {
                $table->index(['user_id', 'mastered', 'question_id', 'attempt_count', 'correct_streak', 'created_at'], 'mistakes_review_brutal_idx');
            }
            
            // Covering index for question difficulty analysis
            if (!DB::select("SHOW INDEX FROM mistakes WHERE Key_name = 'mistakes_difficulty_brutal_idx'")) {
                $table->index(['question_id', 'mastered', 'attempt_count', 'user_id'], 'mistakes_difficulty_brutal_idx');
            }
        });

        // Course access - MAXIMUM SPEED access control
        Schema::table('course_user', function (Blueprint $table) {
            // Covering index for enrollment checks
            if (!DB::select("SHOW INDEX FROM course_user WHERE Key_name = 'course_user_access_brutal_idx'")) {
                $table->index(['user_id', 'course_id', 'enrollment_status', 'enrolled_at', 'completion_percentage'], 'course_user_access_brutal_idx');
            }
        });

        // Notifications - BRUTAL notification performance
        Schema::table('notifications', function (Blueprint $table) {
            // Covering index for user notification dashboard
            if (!DB::select("SHOW INDEX FROM notifications WHERE Key_name = 'notifications_dashboard_brutal_idx'")) {
                $table->index(['user_id', 'is_read', 'created_at', 'type', 'data'], 'notifications_dashboard_brutal_idx');
            }
        });

        // 🔥 ADDITIONAL FULL-TEXT SEARCH INDEXES
        Schema::table('topics', function (Blueprint $table) {
            if (!DB::select("SHOW INDEX FROM topics WHERE Key_name = 'topics_search_brutal_idx'")) {
                $table->fullText(['name'], 'topics_search_brutal_idx');
            }
        });

        // 🔥 PARTIAL INDEXES for extreme optimization (MySQL 8.0+)
        try {
            // Active users only index
            DB::statement("CREATE INDEX users_active_only_brutal_idx ON users(id, email, name) WHERE last_active_at > DATE_SUB(NOW(), INTERVAL 30 DAY)");
        } catch (\Exception $e) {
            // Partial indexes not supported, skip
        }

        try {
            // Recent quiz attempts only
            DB::statement("CREATE INDEX quiz_attempts_recent_brutal_idx ON quiz_attempts(user_id, topic_id, percentage_grade) WHERE created_at > DATE_SUB(NOW(), INTERVAL 90 DAY)");
        } catch (\Exception $e) {
            // Partial indexes not supported, skip
        }

        // 🔥 FUNCTIONAL INDEXES for calculated columns
        try {
            // User accuracy calculation index
            DB::statement("CREATE INDEX users_accuracy_brutal_idx ON users((CASE WHEN total_questions_attempted > 0 THEN (correct_answers_count * 100.0 / total_questions_attempted) ELSE 0 END))");
        } catch (\Exception $e) {
            // Functional indexes not supported, skip
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop functional indexes
        try {
            DB::statement("DROP INDEX users_accuracy_brutal_idx ON users");
        } catch (\Exception $e) {
            // Index may not exist
        }

        // Drop partial indexes
        try {
            DB::statement("DROP INDEX quiz_attempts_recent_brutal_idx ON quiz_attempts");
            DB::statement("DROP INDEX users_active_only_brutal_idx ON users");
        } catch (\Exception $e) {
            // Indexes may not exist
        }

        // Drop covering indexes
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropIndex('notifications_dashboard_brutal_idx');
        });

        Schema::table('course_user', function (Blueprint $table) {
            $table->dropIndex('course_user_access_brutal_idx');
        });

        Schema::table('mistakes', function (Blueprint $table) {
            $table->dropIndex('mistakes_difficulty_brutal_idx');
            $table->dropIndex('mistakes_review_brutal_idx');
        });

        Schema::table('topics', function (Blueprint $table) {
            $table->dropFullText('topics_search_brutal_idx');
            $table->dropIndex('topics_course_listing_brutal_idx');
        });

        Schema::table('questions', function (Blueprint $table) {
            $table->dropIndex('questions_full_covering_brutal_idx');
            $table->dropIndex('questions_quiz_generation_brutal_idx');
        });

        Schema::table('quiz_attempts', function (Blueprint $table) {
            $table->dropIndex('quiz_attempts_topic_stats_brutal_idx');
            $table->dropIndex('quiz_attempts_performance_brutal_idx');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('users_stats_covering_brutal_idx');
            $table->dropIndex('users_auth_covering_brutal_idx');
        });
    }
};