<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * ULTIMATE PERFORMANCE OPTIMIZATION MIGRATION
     * Fixes critical database performance bottlenecks identified in audit
     */
    public function up(): void
    {
        // 1. CRITICAL COMPOSITE INDEXES FOR HOT QUERIES
        Schema::table('quiz_attempts', function (Blueprint $table) {
            // Hot path: User's recent quiz attempts for analytics
            if (!$this->indexExists('quiz_attempts', 'idx_quiz_user_topic_created')) {
                $table->index(['user_id', 'topic_id', 'created_at'], 'idx_quiz_user_topic_created');
            }
            
            // Hot path: Topic performance analysis
            if (!$this->indexExists('quiz_attempts', 'idx_quiz_topic_score_created')) {
                $table->index(['topic_id', 'score', 'created_at'], 'idx_quiz_topic_score_created');
            }
            
            // Hot path: User performance over time
            if (!$this->indexExists('quiz_attempts', 'idx_quiz_user_created_score')) {
                $table->index(['user_id', 'created_at', 'score'], 'idx_quiz_user_created_score');
            }
        });

        Schema::table('questions', function (Blueprint $table) {
            // Hot path: Question difficulty analysis
            if (!$this->indexExists('questions', 'idx_questions_topic_difficulty')) {
                $table->index(['topic_id', 'total_attempts', 'correct_attempts'], 'idx_questions_topic_difficulty');
            }
            
            // Hot path: Question success rate filtering
            if (!$this->indexExists('questions', 'idx_questions_success_rate')) {
                $table->index(['topic_id', 'correct_attempts', 'total_attempts'], 'idx_questions_success_rate');
            }
            
            // Hot path: Recent question activity
            if (!$this->indexExists('questions', 'idx_questions_topic_recent')) {
                $table->index(['topic_id', 'last_attempted_at', 'total_attempts'], 'idx_questions_topic_recent');
            }
        });

        Schema::table('mistakes', function (Blueprint $table) {
            // Hot path: User's recent mistakes for learning
            if (!$this->indexExists('mistakes', 'idx_mistakes_user_recent')) {
                $table->index(['user_id', 'last_attempt_date'], 'idx_mistakes_user_recent');
            }
            
            // Hot path: Mistake analysis by question
            if (!$this->indexExists('mistakes', 'idx_mistakes_question_recent')) {
                $table->index(['question_id', 'last_attempt_date'], 'idx_mistakes_question_recent');
            }
            
            // Hot path: User mistake patterns
            if (!$this->indexExists('mistakes', 'idx_mistakes_user_question_count')) {
                $table->index(['user_id', 'question_id'], 'idx_mistakes_user_question_count');
            }
        });

        Schema::table('users', function (Blueprint $table) {
            // Hot path: User performance tracking
            if (!$this->indexExists('users', 'idx_users_performance')) {
                $table->index(['correct_answers_count', 'total_questions_attempted', 'xp'], 'idx_users_performance');
            }
            
            // Hot path: Active user analysis
            if (!$this->indexExists('users', 'idx_users_activity')) {
                $table->index(['last_active_at', 'study_streak_days', 'role'], 'idx_users_activity');
            }
        });

        // 2. OPTIMIZE SESSION TABLE FOR REAL-TIME ANALYTICS
        Schema::table('sessions', function (Blueprint $table) {
            // Hot path: Active user counting
            if (!$this->indexExists('sessions', 'idx_sessions_active_users')) {
                $table->index(['last_activity', 'user_id', 'is_visitor'], 'idx_sessions_active_users');
            }
            
            // Hot path: Session fingerprint analysis
            if (!$this->indexExists('sessions', 'idx_sessions_fingerprint_activity')) {
                $table->index(['fingerprint', 'last_activity', 'is_visitor'], 'idx_sessions_fingerprint_activity');
            }
        });

        // 3. TOPIC AND COURSE OPTIMIZATION
        Schema::table('topics', function (Blueprint $table) {
            // Hot path: Course topic ordering with progress
            if (!$this->indexExists('topics', 'idx_topics_course_progress')) {
                $table->index(['course_id', 'display_order', 'percentage_grade'], 'idx_topics_course_progress');
            }
        });

        Schema::table('course_user', function (Blueprint $table) {
            // Hot path: User enrollment status
            if (!$this->indexExists('course_user', 'idx_course_user_status_date')) {
                $table->index(['user_id', 'enrollment_status', 'enrolled_at'], 'idx_course_user_status_date');
            }
            
            // Hot path: Course enrollment analytics
            if (!$this->indexExists('course_user', 'idx_course_enrollment_stats')) {
                $table->index(['course_id', 'enrollment_status', 'enrolled_at'], 'idx_course_enrollment_stats');
            }
        });

        // 4. NOTIFICATION SYSTEM OPTIMIZATION
        Schema::table('notifications', function (Blueprint $table) {
            // Hot path: User unread notifications
            if (!$this->indexExists('notifications', 'idx_notifications_user_unread')) {
                $table->index(['user_id', 'read_at', 'created_at'], 'idx_notifications_user_unread');
            }
            
            // Hot path: Notification type analysis
            if (!$this->indexExists('notifications', 'idx_notifications_type_created')) {
                $table->index(['type', 'created_at', 'read_at'], 'idx_notifications_type_created');
            }
        });

        // 5. PAYMENT SYSTEM OPTIMIZATION
        if (Schema::hasTable('payments')) {
            Schema::table('payments', function (Blueprint $table) {
                // Hot path: User payment history
                if (!$this->indexExists('payments', 'idx_payments_user_status_date')) {
                    $table->index(['user_id', 'status', 'created_at'], 'idx_payments_user_status_date');
                }
                
                // Hot path: Payment verification queue
                if (!$this->indexExists('payments', 'idx_payments_verification_queue')) {
                    $table->index(['status', 'created_at', 'amount'], 'idx_payments_verification_queue');
                }
            });
        }

        // 6. DATABASE QUERY OPTIMIZATION SETTINGS
        // Commented out for MySQL 8.0+ compatibility
        // DB::statement('SET SESSION query_cache_type = ON');
        // DB::statement('SET SESSION query_cache_size = 268435456'); // 256MB
        
        // 7. ANALYZE TABLES FOR OPTIMAL QUERY EXECUTION
        $tables = [
            'users', 'questions', 'topics', 'courses', 'quiz_attempts', 
            'mistakes', 'notifications', 'sessions', 'course_user'
        ];
        
        foreach ($tables as $table) {
            if (Schema::hasTable($table)) {
                DB::statement("ANALYZE TABLE {$table}");
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop indexes in reverse order
        $indexesToDrop = [
            'sessions' => ['idx_sessions_fingerprint_activity', 'idx_sessions_active_users'],
            'quiz_attempts' => ['idx_quiz_user_created_score', 'idx_quiz_topic_score_created', 'idx_quiz_user_topic_created'],
            'questions' => ['idx_questions_topic_recent', 'idx_questions_success_rate', 'idx_questions_topic_difficulty'],
            'mistakes' => ['idx_mistakes_user_question_count', 'idx_mistakes_question_recent', 'idx_mistakes_user_recent'],
            'users' => ['idx_users_activity', 'idx_users_performance'],
            'topics' => ['idx_topics_course_progress'],
            'course_user' => ['idx_course_enrollment_stats', 'idx_course_user_status_date'],
            'notifications' => ['idx_notifications_type_created', 'idx_notifications_user_unread'],
        ];

        if (Schema::hasTable('payments')) {
            $indexesToDrop['payments'] = ['idx_payments_verification_queue', 'idx_payments_user_status_date'];
        }

        foreach ($indexesToDrop as $table => $indexes) {
            if (Schema::hasTable($table)) {
                Schema::table($table, function (Blueprint $table) use ($indexes) {
                    foreach ($indexes as $index) {
                        if ($this->indexExists($table->getTable(), $index)) {
                            $table->dropIndex($index);
                        }
                    }
                });
            }
        }
    }

    /**
     * Check if an index exists on a table
     */
    private function indexExists(string $table, string $indexName): bool
    {
        try {
            $indexes = DB::select("SHOW INDEX FROM {$table} WHERE Key_name = ?", [$indexName]);
            return !empty($indexes);
        } catch (\Exception $e) {
            return false;
        }
    }
};