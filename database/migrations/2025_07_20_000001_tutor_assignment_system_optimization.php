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
        try {
            $indexes = collect(DB::select("SHOW INDEX FROM `{$table}`"));
            return $indexes->contains('Key_name', $indexName);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Check if an index is part of a foreign key constraint
     */
    private function isIndexPartOfForeignKey(string $table, string $indexName): bool
    {
        try {
            $foreignKeys = collect(DB::select("
                SELECT CONSTRAINT_NAME, COLUMN_NAME 
                FROM information_schema.KEY_COLUMN_USAGE 
                WHERE TABLE_SCHEMA = DATABASE() 
                AND TABLE_NAME = '{$table}' 
                AND REFERENCED_TABLE_NAME IS NOT NULL
            "));
            
            $indexes = collect(DB::select("SHOW INDEX FROM `{$table}`"));
            $indexColumns = $indexes->where('Key_name', $indexName)->pluck('Column_name');
            
            foreach ($foreignKeys as $fk) {
                if ($indexColumns->contains($fk->COLUMN_NAME)) {
                    return true;
                }
            }
            
            return false;
        } catch (\Exception $e) {
            return true; // Assume it's part of FK if we can't check
        }
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

        // Skip skipped_questions table - removed from schema

        Schema::table('topics', function (Blueprint $table) {
            // Enhanced locking and performance indexes
            if (!$this->indexExists('topics', 'idx_topics_course_locked_order')) {
                $table->index(['course_id', 'is_locked', 'display_order'], 'idx_topics_course_locked_order');
            }
            // REMOVED: Indexes for user_id, mastered, last_attempt_date - these fields are in user_topic_progress table
            // Topics belong to courses, not individual users
        });

        Schema::table('courses', function (Blueprint $table) {
            // Course filtering and performance indexes
            if (!$this->indexExists('courses', 'idx_courses_academic_year_price_currency')) {
                $table->index(['academic_year', 'price', 'currency'], 'idx_courses_academic_year_price_currency');
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
                $table->index(['topic_id'], 'idx_questions_topic_deleted');
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
                COUNT(CASE WHEN utp.percentage_grade >= 80 THEN 1 END) as mastered_topics,
                AVG(COALESCE(utp.percentage_grade, 0)) as average_grade,
                COUNT(a.id) as total_assignments,
                COUNT(CASE WHEN a.status = "completed" THEN 1 END) as completed_assignments
            FROM users u
            JOIN course_user cu ON u.id = cu.user_id
            JOIN courses c ON cu.course_id = c.id
            LEFT JOIN topics t ON c.id = t.course_id
            LEFT JOIN user_topic_progress utp ON t.id = utp.topic_id AND u.id = utp.user_id
            LEFT JOIN assignments a ON u.id = a.student_id AND c.id = a.course_id
            WHERE u.role = "student"
            GROUP BY u.id, c.id
        ');
    }

    /**
     * Safely drop an index if it exists and is not part of a foreign key
     */
    private function safeDropIndex(string $table, string $indexName): void
    {
        try {
            if ($this->indexExists($table, $indexName) && !$this->isIndexPartOfForeignKey($table, $indexName)) {
                DB::statement("ALTER TABLE `{$table}` DROP INDEX `{$indexName}`");
            }
        } catch (\Exception $e) {
            // Silently continue if index can't be dropped
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop views first - they don't have dependencies
        try {
            DB::statement('DROP VIEW IF EXISTS assignment_dashboard_view');
            DB::statement('DROP VIEW IF EXISTS student_progress_view');
        } catch (\Exception $e) {
            // Views might not exist, continue
        }

        // Define all indexes to drop with their tables
        $indexesToDrop = [
            'assignments' => [
                'idx_assignments_status_deadline',
                'idx_assignments_tutor_status_deadline', 
                'idx_assignments_student_course_status',
                'idx_assignments_topic_status',
                'idx_assignments_completed_status'
            ],
            'topics' => [
                'idx_topics_course_locked_order',
                'idx_topics_user_mastered',
                'idx_topics_attempt_mastered'
            ],
            'courses' => [
                'idx_courses_academic_year_price_currency',
                'idx_courses_cache_version'
            ],
            'users' => [
                'idx_users_role',
                'idx_users_email_verified'
            ],
            'tutor_student_relationships' => [
                'idx_tutor_student_active',
                'idx_student_tutor_lookup'
            ],
            'questions' => [
                'idx_questions_topic_deleted',
                'idx_questions_source_type'
            ],
            'quiz_attempts' => [
                'idx_quiz_attempts_user_topic',
                'idx_quiz_attempts_score_time'
            ],
            'mistakes' => [
                'idx_mistakes_user_question',
                'idx_mistakes_mastered_user'
            ]
        ];

        // Drop indexes safely - reverse order for dependencies
        foreach (array_reverse($indexesToDrop, true) as $table => $indexes) {
            if (Schema::hasTable($table)) {
                foreach ($indexes as $index) {
                    $this->safeDropIndex($table, $index);
                }
            }
        }
    }
};