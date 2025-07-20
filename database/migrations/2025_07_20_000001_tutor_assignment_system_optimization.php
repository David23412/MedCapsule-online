<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Check if an index exists on a table
     */
    private function indexExists(string $table, string $indexName): bool
    {
        $indexes = collect(DB::select("SHOW INDEX FROM {$table}"));
        return $indexes->contains('Key_name', $indexName);
    }

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add comprehensive indexes for assignment system performance
        Schema::table('assignments', function (Blueprint $table) {
            // Check and add composite indexes for complex queries
            if (!$this->indexExists('assignments', 'idx_assignments_status_deadline')) {
                $table->index(['status', 'deadline'], 'idx_assignments_status_deadline');
            }
            if (!$this->indexExists('assignments', 'idx_assignments_tutor_status_deadline')) {
                $table->index(['tutor_id', 'status', 'deadline'], 'idx_assignments_tutor_status_deadline');
            }
            if (!$this->indexExists('assignments', 'idx_assignments_student_course_status')) {
                $table->index(['student_id', 'course_id', 'status'], 'idx_assignments_student_course_status');
            }
            if (!$this->indexExists('assignments', 'idx_assignments_topic_status')) {
                $table->index(['topic_id', 'status'], 'idx_assignments_topic_status');
            }
            if (!$this->indexExists('assignments', 'idx_assignments_completed_status')) {
                $table->index(['completed_at', 'status'], 'idx_assignments_completed_status');
            }
        });

        Schema::table('skipped_questions', function (Blueprint $table) {
            // Additional performance indexes
            if (!$this->indexExists('skipped_questions', 'idx_skipped_time_session')) {
                $table->index(['skipped_at', 'session_type'], 'idx_skipped_time_session');
            }
            if (!$this->indexExists('skipped_questions', 'idx_skipped_user_time')) {
                $table->index(['user_id', 'skipped_at'], 'idx_skipped_user_time');
            }
        });

        Schema::table('topics', function (Blueprint $table) {
            // Enhanced locking and performance indexes
            if (!$this->indexExists('topics', 'idx_topics_course_locked_order')) {
                $table->index(['course_id', 'is_locked', 'display_order'], 'idx_topics_course_locked_order');
            }
            if (!$this->indexExists('topics', 'idx_topics_user_mastered')) {
                $table->index(['user_id', 'mastered'], 'idx_topics_user_mastered');
            }
            if (!$this->indexExists('topics', 'idx_topics_attempt_mastered')) {
                $table->index(['last_attempt_date', 'mastered'], 'idx_topics_attempt_mastered');
            }
        });

        Schema::table('courses', function (Blueprint $table) {
            // Course filtering and performance indexes
            if (!$this->indexExists('courses', 'idx_courses_year_price_currency')) {
                $table->index(['year', 'price', 'currency'], 'idx_courses_year_price_currency');
            }
            if (!$this->indexExists('courses', 'idx_courses_cache_version')) {
                $table->index('cache_version', 'idx_courses_cache_version');
            }
        });

        Schema::table('users', function (Blueprint $table) {
            // User role and tutor relationship indexes
            if (!$this->indexExists('users', 'idx_users_role')) {
                $table->index('role', 'idx_users_role');
            }
            if (!$this->indexExists('users', 'idx_users_email_verified')) {
                $table->index('email_verified_at', 'idx_users_email_verified');
            }
        });

        Schema::table('tutor_student_relationships', function (Blueprint $table) {
            // Tutor-student relationship performance indexes
            if (!$this->indexExists('tutor_student_relationships', 'idx_tutor_student_active')) {
                $table->index(['tutor_id', 'student_id'], 'idx_tutor_student_active');
            }
            if (!$this->indexExists('tutor_student_relationships', 'idx_student_tutor_lookup')) {
                $table->index(['student_id', 'tutor_id'], 'idx_student_tutor_lookup');
            }
        });

        Schema::table('questions', function (Blueprint $table) {
            // Question filtering and performance indexes
            if (!$this->indexExists('questions', 'idx_questions_topic_deleted')) {
                $table->index(['topic_id', 'deleted'], 'idx_questions_topic_deleted');
            }
            if (!$this->indexExists('questions', 'idx_questions_source_type')) {
                $table->index(['source', 'question_type'], 'idx_questions_source_type');
            }
        });

        Schema::table('quiz_attempts', function (Blueprint $table) {
            // Quiz attempt performance indexes
            if (!$this->indexExists('quiz_attempts', 'idx_quiz_attempts_user_topic')) {
                $table->index(['user_id', 'topic_id', 'created_at'], 'idx_quiz_attempts_user_topic');
            }
            if (!$this->indexExists('quiz_attempts', 'idx_quiz_attempts_score_time')) {
                $table->index(['score', 'created_at'], 'idx_quiz_attempts_score_time');
            }
        });

        Schema::table('mistakes', function (Blueprint $table) {
            // Mistake tracking performance indexes
            if (!$this->indexExists('mistakes', 'idx_mistakes_user_question')) {
                $table->index(['user_id', 'question_id'], 'idx_mistakes_user_question');
            }
            if (!$this->indexExists('mistakes', 'idx_mistakes_mastered_user')) {
                $table->index(['mastered', 'user_id'], 'idx_mistakes_mastered_user');
            }
        });

        // Create database views for common queries
        DB::statement('
            CREATE OR REPLACE VIEW assignment_dashboard_view AS
            SELECT 
                a.id,
                a.title,
                a.deadline,
                a.status,
                a.score,
                a.completed_at,
                t.name as topic_name,
                c.name as course_name,
                s.name as student_name,
                s.email as student_email,
                tutor.name as tutor_name
            FROM assignments a
            JOIN topics t ON a.topic_id = t.id
            JOIN courses c ON a.course_id = c.id
            JOIN users s ON a.student_id = s.id
            JOIN users tutor ON a.tutor_id = tutor.id
        ');

        DB::statement('
            CREATE OR REPLACE VIEW student_progress_view AS
            SELECT 
                u.id as user_id,
                u.name as student_name,
                c.id as course_id,
                c.name as course_name,
                COUNT(t.id) as total_topics,
                COUNT(CASE WHEN t.mastered = 1 THEN 1 END) as mastered_topics,
                AVG(t.percentage_grade) as average_grade,
                COUNT(a.id) as total_assignments,
                COUNT(CASE WHEN a.status = "completed" THEN 1 END) as completed_assignments
            FROM users u
            JOIN course_user cu ON u.id = cu.user_id
            JOIN courses c ON cu.course_id = c.id
            LEFT JOIN topics t ON c.id = t.course_id AND t.user_id = u.id
            LEFT JOIN assignments a ON u.id = a.student_id AND c.id = a.course_id
            WHERE u.role = "student"
            GROUP BY u.id, c.id
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop views
        DB::statement('DROP VIEW IF EXISTS assignment_dashboard_view');
        DB::statement('DROP VIEW IF EXISTS student_progress_view');

        // Drop indexes in reverse order
        Schema::table('mistakes', function (Blueprint $table) {
            $table->dropIndex('idx_mistakes_user_question');
            $table->dropIndex('idx_mistakes_mastered_user');
        });

        Schema::table('quiz_attempts', function (Blueprint $table) {
            $table->dropIndex('idx_quiz_attempts_user_topic');
            $table->dropIndex('idx_quiz_attempts_score_time');
        });

        Schema::table('questions', function (Blueprint $table) {
            $table->dropIndex('idx_questions_topic_deleted');
            $table->dropIndex('idx_questions_source_type');
        });

        Schema::table('tutor_student_relationships', function (Blueprint $table) {
            $table->dropIndex('idx_tutor_student_active');
            $table->dropIndex('idx_student_tutor_lookup');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('idx_users_role');
            $table->dropIndex('idx_users_email_verified');
        });

        Schema::table('courses', function (Blueprint $table) {
            $table->dropIndex('idx_courses_year_price_currency');
            $table->dropIndex('idx_courses_cache_version');
        });

        Schema::table('topics', function (Blueprint $table) {
            $table->dropIndex('idx_topics_course_locked_order');
            $table->dropIndex('idx_topics_user_mastered');
            $table->dropIndex('idx_topics_attempt_mastered');
        });

        Schema::table('skipped_questions', function (Blueprint $table) {
            $table->dropIndex('idx_skipped_time_session');
            $table->dropIndex('idx_skipped_user_time');
        });

        Schema::table('assignments', function (Blueprint $table) {
            $table->dropIndex('idx_assignments_status_deadline');
            $table->dropIndex('idx_assignments_tutor_status_deadline');
            $table->dropIndex('idx_assignments_student_course_status');
            $table->dropIndex('idx_assignments_topic_status');
            $table->dropIndex('idx_assignments_completed_status');
        });
    }
};