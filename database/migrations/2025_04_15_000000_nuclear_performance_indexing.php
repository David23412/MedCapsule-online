<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * 🔥 NUCLEAR PERFORMANCE INDEXING SYSTEM 🔥
     * 
     * ULTRA-EFFICIENT STRATEGY:
     * - Target ONLY critical query patterns
     * - Composite indexes for multi-column WHERE clauses
     * - Covering indexes for SELECT optimization
     * - NO BLOAT - Every index serves a purpose
     */
    public function up(): void
    {
        // 🎯 QUIZ ATTEMPTS: Most critical table for performance
        Schema::table('quiz_attempts', function (Blueprint $table) {
            // CRITICAL: User progress queries (topics page)
            $table->index(['user_id', 'topic_id', 'study_mode'], 'idx_user_topic_mode_performance');
            
            // CRITICAL: Assignment progress queries (NEW ARCHITECTURE)
            $table->index(['assignment_id', 'user_id', 'percentage_grade'], 'idx_assignment_user_grade');
            
            // CRITICAL: Source-specific progress
            $table->index(['user_id', 'topic_id', 'source'], 'idx_user_topic_source');
            
            // CRITICAL: Latest attempts (ORDER BY created_at DESC)
            $table->index(['user_id', 'created_at'], 'idx_user_latest_attempts');
            
            // CRITICAL: Performance analytics
            $table->index(['quiz_type', 'created_at'], 'idx_quiz_type_date');
        });

        // 🎯 ASSIGNMENTS: Template-based queries
        Schema::table('assignments', function (Blueprint $table) {
            // CRITICAL: Course assignments listing
            $table->index(['course_id', 'status', 'deadline'], 'idx_course_active_assignments');
            
            // CRITICAL: Topic assignments
            $table->index(['topic_id', 'deadline'], 'idx_topic_assignments');
            
            // CRITICAL: Overdue detection
            $table->index(['status', 'deadline'], 'idx_status_deadline');
        });

        // 🎯 QUESTIONS: Quiz generation performance
        Schema::table('questions', function (Blueprint $table) {
            // CRITICAL: Topic-source quiz generation
            $table->index(['topic_id', 'source'], 'idx_topic_source_questions');
            
            // CRITICAL: Random quiz generation
            $table->index(['topic_id', 'created_at'], 'idx_topic_random');
        });

        // 🎯 TOPICS: Course navigation
        Schema::table('topics', function (Blueprint $table) {
            // CRITICAL: Course topics ordering
            $table->index(['course_id', 'display_order'], 'idx_course_topic_order');
        });

        // 🎯 USERS: Authentication and role queries
        Schema::table('users', function (Blueprint $table) {
            // CRITICAL: Role-based queries
            $table->index(['role', 'created_at'], 'idx_role_date');
            
            // CRITICAL: Active users
            $table->index(['email_verified_at', 'role'], 'idx_verified_role');
        });

        // 🎯 PAYMENTS: Financial queries (only if table exists)
        if (Schema::hasTable('payments')) {
            Schema::table('payments', function (Blueprint $table) {
                // CRITICAL: User payment history
                $table->index(['user_id', 'status', 'created_at'], 'idx_user_payment_history');
                
                // CRITICAL: Course revenue analytics
                $table->index(['course_id', 'status', 'paid_at'], 'idx_course_revenue');
            });
        }

        // 🎯 MISTAKES: Learning analytics
        Schema::table('mistakes', function (Blueprint $table) {
            // CRITICAL: User mistake review (no topic_id in mistakes table)
            $table->index(['user_id', 'last_attempt_date'], 'idx_user_mistakes_date');
            
            // CRITICAL: Question difficulty analysis
            $table->index(['question_id', 'created_at'], 'idx_question_mistakes');
        });

        // 🎯 COURSE_USER: Enrollment queries
        Schema::table('course_user', function (Blueprint $table) {
            // CRITICAL: User courses with enrollment status
            $table->index(['user_id', 'is_enrolled', 'created_at'], 'idx_user_enrollments');
            
            // CRITICAL: Course students with enrollment status
            $table->index(['course_id', 'is_enrolled', 'created_at'], 'idx_course_students');
            
            // CRITICAL: Access control queries
            $table->index(['user_id', 'course_id', 'is_enrolled'], 'idx_user_course_access');
            
            // CRITICAL: Admin enrollment management
            $table->index(['is_enrolled', 'created_at'], 'idx_enrollment_status_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quiz_attempts', function (Blueprint $table) {
            $table->dropIndex('idx_user_topic_mode_performance');
            $table->dropIndex('idx_assignment_user_grade');
            $table->dropIndex('idx_user_topic_source');
            $table->dropIndex('idx_user_latest_attempts');
            $table->dropIndex('idx_quiz_type_date');
        });

        Schema::table('assignments', function (Blueprint $table) {
            $table->dropIndex('idx_course_active_assignments');
            $table->dropIndex('idx_topic_assignments');
            $table->dropIndex('idx_status_deadline');
        });

        Schema::table('questions', function (Blueprint $table) {
            $table->dropIndex('idx_topic_source_questions');
            $table->dropIndex('idx_topic_random');
        });

        Schema::table('topics', function (Blueprint $table) {
            $table->dropIndex('idx_course_topic_order');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('idx_role_date');
            $table->dropIndex('idx_verified_role');
        });

        if (Schema::hasTable('payments')) {
            Schema::table('payments', function (Blueprint $table) {
                $table->dropIndex('idx_user_payment_history');
                $table->dropIndex('idx_course_revenue');
            });
        }

        Schema::table('mistakes', function (Blueprint $table) {
            $table->dropIndex('idx_user_mistakes_date');
            $table->dropIndex('idx_question_mistakes');
        });

        Schema::table('course_user', function (Blueprint $table) {
            $table->dropIndex('idx_user_enrollments');
            $table->dropIndex('idx_course_students');
            $table->dropIndex('idx_user_course_access');
            $table->dropIndex('idx_enrollment_status_date');
        });
    }
};