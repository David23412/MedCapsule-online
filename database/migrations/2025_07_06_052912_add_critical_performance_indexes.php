<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Users table indexes
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'created_at') && !DB::select("SHOW INDEX FROM users WHERE Key_name = 'users_created_at_index'")) {
                $table->index('created_at', 'users_created_at_index');
            }
            if (Schema::hasColumn('users', 'is_admin') && Schema::hasColumn('users', 'email_verified_at') && !DB::select("SHOW INDEX FROM users WHERE Key_name = 'users_admin_verified_index'")) {
                $table->index(['is_admin', 'email_verified_at'], 'users_admin_verified_index');
            }
        });

        // Questions table indexes
        Schema::table('questions', function (Blueprint $table) {
            if (Schema::hasColumn('questions', 'topic_id') && !DB::select("SHOW INDEX FROM questions WHERE Key_name = 'questions_topic_id_index'")) {
                $table->index('topic_id', 'questions_topic_id_index');
            }
            if (Schema::hasColumn('questions', 'created_at') && !DB::select("SHOW INDEX FROM questions WHERE Key_name = 'questions_created_at_index'")) {
                $table->index('created_at', 'questions_created_at_index');
            }
            if (Schema::hasColumn('questions', 'difficulty_level')) {
                if (!DB::select("SHOW INDEX FROM questions WHERE Key_name = 'questions_difficulty_index'")) {
                    $table->index('difficulty_level', 'questions_difficulty_index');
                }
                if (Schema::hasColumn('questions', 'topic_id') && !DB::select("SHOW INDEX FROM questions WHERE Key_name = 'questions_topic_difficulty_index'")) {
                    $table->index(['topic_id', 'difficulty_level'], 'questions_topic_difficulty_index');
                }
            }
        });

        // Topics table indexes
        Schema::table('topics', function (Blueprint $table) {
            if (Schema::hasColumn('topics', 'course_id') && !DB::select("SHOW INDEX FROM topics WHERE Key_name = 'topics_course_id_index'")) {
                $table->index('course_id', 'topics_course_id_index');
            }
            if (Schema::hasColumn('topics', 'order')) {
                if (Schema::hasColumn('topics', 'course_id') && !DB::select("SHOW INDEX FROM topics WHERE Key_name = 'topics_course_order_index'")) {
                    $table->index(['course_id', 'order'], 'topics_course_order_index');
                }
            }
        });

        // Courses table indexes
        Schema::table('courses', function (Blueprint $table) {
            if (Schema::hasColumn('courses', 'is_active') && !DB::select("SHOW INDEX FROM courses WHERE Key_name = 'courses_active_index'")) {
                $table->index('is_active', 'courses_active_index');
            }
            if (Schema::hasColumn('courses', 'created_at') && !DB::select("SHOW INDEX FROM courses WHERE Key_name = 'courses_created_at_index'")) {
                $table->index('created_at', 'courses_created_at_index');
            }
        });

        // Quiz attempts table indexes
        Schema::table('quiz_attempts', function (Blueprint $table) {
            if (Schema::hasColumn('quiz_attempts', 'user_id') && !DB::select("SHOW INDEX FROM quiz_attempts WHERE Key_name = 'quiz_attempts_user_index'")) {
                $table->index('user_id', 'quiz_attempts_user_index');
            }
            if (Schema::hasColumn('quiz_attempts', 'topic_id') && !DB::select("SHOW INDEX FROM quiz_attempts WHERE Key_name = 'quiz_attempts_topic_index'")) {
                $table->index('topic_id', 'quiz_attempts_topic_index');
            }
            if (Schema::hasColumn('quiz_attempts', 'user_id') && Schema::hasColumn('quiz_attempts', 'topic_id') && !DB::select("SHOW INDEX FROM quiz_attempts WHERE Key_name = 'quiz_attempts_user_topic_index'")) {
                $table->index(['user_id', 'topic_id'], 'quiz_attempts_user_topic_index');
            }
            if (Schema::hasColumn('quiz_attempts', 'created_at') && !DB::select("SHOW INDEX FROM quiz_attempts WHERE Key_name = 'quiz_attempts_created_at_index'")) {
                $table->index('created_at', 'quiz_attempts_created_at_index');
            }
        });

        // User progress table indexes (if table exists)
        if (Schema::hasTable('user_progress')) {
            Schema::table('user_progress', function (Blueprint $table) {
                if (Schema::hasColumn('user_progress', 'user_id') && !DB::select("SHOW INDEX FROM user_progress WHERE Key_name = 'user_progress_user_index'")) {
                    $table->index('user_id', 'user_progress_user_index');
                }
                if (Schema::hasColumn('user_progress', 'topic_id') && !DB::select("SHOW INDEX FROM user_progress WHERE Key_name = 'user_progress_topic_index'")) {
                    $table->index('topic_id', 'user_progress_topic_index');
                }
                if (Schema::hasColumn('user_progress', 'user_id') && Schema::hasColumn('user_progress', 'topic_id') && !DB::select("SHOW INDEX FROM user_progress WHERE Key_name = 'user_progress_user_topic_index'")) {
                    $table->index(['user_id', 'topic_id'], 'user_progress_user_topic_index');
                }
            });
        }

        // Payments table indexes
        Schema::table('payments', function (Blueprint $table) {
            if (Schema::hasColumn('payments', 'user_id') && !DB::select("SHOW INDEX FROM payments WHERE Key_name = 'payments_user_index'")) {
                $table->index('user_id', 'payments_user_index');
            }
            if (Schema::hasColumn('payments', 'status') && !DB::select("SHOW INDEX FROM payments WHERE Key_name = 'payments_status_index'")) {
                $table->index('status', 'payments_status_index');
            }
            if (Schema::hasColumn('payments', 'user_id') && Schema::hasColumn('payments', 'status') && !DB::select("SHOW INDEX FROM payments WHERE Key_name = 'payments_user_status_index'")) {
                $table->index(['user_id', 'status'], 'payments_user_status_index');
            }
            if (Schema::hasColumn('payments', 'created_at') && !DB::select("SHOW INDEX FROM payments WHERE Key_name = 'payments_created_at_index'")) {
                $table->index('created_at', 'payments_created_at_index');
            }
        });

        // Mistakes table indexes
        Schema::table('mistakes', function (Blueprint $table) {
            if (Schema::hasColumn('mistakes', 'user_id') && !DB::select("SHOW INDEX FROM mistakes WHERE Key_name = 'mistakes_user_index'")) {
                $table->index('user_id', 'mistakes_user_index');
            }
            if (Schema::hasColumn('mistakes', 'question_id') && !DB::select("SHOW INDEX FROM mistakes WHERE Key_name = 'mistakes_question_index'")) {
                $table->index('question_id', 'mistakes_question_index');
            }
            if (Schema::hasColumn('mistakes', 'user_id') && Schema::hasColumn('mistakes', 'question_id') && !DB::select("SHOW INDEX FROM mistakes WHERE Key_name = 'mistakes_user_question_index'")) {
                $table->index(['user_id', 'question_id'], 'mistakes_user_question_index');
            }
            if (Schema::hasColumn('mistakes', 'created_at') && !DB::select("SHOW INDEX FROM mistakes WHERE Key_name = 'mistakes_created_at_index'")) {
                $table->index('created_at', 'mistakes_created_at_index');
            }
        });

        // Notifications table indexes
        Schema::table('notifications', function (Blueprint $table) {
            if (Schema::hasColumn('notifications', 'notifiable_id') && !DB::select("SHOW INDEX FROM notifications WHERE Key_name = 'notifications_notifiable_index'")) {
                $table->index('notifiable_id', 'notifications_notifiable_index');
            }
            if (Schema::hasColumn('notifications', 'read_at') && !DB::select("SHOW INDEX FROM notifications WHERE Key_name = 'notifications_read_at_index'")) {
                $table->index('read_at', 'notifications_read_at_index');
            }
            if (Schema::hasColumn('notifications', 'notifiable_id') && Schema::hasColumn('notifications', 'read_at') && !DB::select("SHOW INDEX FROM notifications WHERE Key_name = 'notifications_notifiable_read_index'")) {
                $table->index(['notifiable_id', 'read_at'], 'notifications_notifiable_read_index');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop all indexes in reverse order
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropIndex('notifications_notifiable_read_index');
            $table->dropIndex('notifications_read_at_index');
            $table->dropIndex('notifications_notifiable_index');
        });

        Schema::table('mistakes', function (Blueprint $table) {
            $table->dropIndex('mistakes_created_at_index');
            $table->dropIndex('mistakes_user_question_index');
            $table->dropIndex('mistakes_question_index');
            $table->dropIndex('mistakes_user_index');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex('payments_created_at_index');
            $table->dropIndex('payments_user_status_index');
            $table->dropIndex('payments_status_index');
            $table->dropIndex('payments_user_index');
        });

        Schema::table('user_progress', function (Blueprint $table) {
            $table->dropIndex('user_progress_user_topic_index');
            $table->dropIndex('user_progress_topic_index');
            $table->dropIndex('user_progress_user_index');
        });

        Schema::table('quiz_attempts', function (Blueprint $table) {
            $table->dropIndex('quiz_attempts_created_at_index');
            $table->dropIndex('quiz_attempts_user_topic_index');
            $table->dropIndex('quiz_attempts_topic_index');
            $table->dropIndex('quiz_attempts_user_index');
        });

        Schema::table('courses', function (Blueprint $table) {
            $table->dropIndex('courses_created_at_index');
            $table->dropIndex('courses_active_index');
        });

        Schema::table('topics', function (Blueprint $table) {
            $table->dropIndex('topics_course_order_index');
            $table->dropIndex('topics_course_id_index');
        });

        Schema::table('questions', function (Blueprint $table) {
            $table->dropIndex('questions_created_at_index');
            $table->dropIndex('questions_topic_difficulty_index');
            $table->dropIndex('questions_difficulty_index');
            $table->dropIndex('questions_topic_id_index');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('users_admin_verified_index');
            $table->dropIndex('users_created_at_index');
        });
    }
};
