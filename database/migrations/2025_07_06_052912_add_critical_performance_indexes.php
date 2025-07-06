<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Users table indexes
        Schema::table('users', function (Blueprint $table) {
            $table->index('email', 'users_email_index');
            $table->index('created_at', 'users_created_at_index');
            $table->index(['is_admin', 'email_verified_at'], 'users_admin_verified_index');
        });

        // Questions table indexes
        Schema::table('questions', function (Blueprint $table) {
            $table->index('topic_id', 'questions_topic_id_index');
            $table->index('difficulty_level', 'questions_difficulty_index');
            $table->index(['topic_id', 'difficulty_level'], 'questions_topic_difficulty_index');
            $table->index('created_at', 'questions_created_at_index');
        });

        // Topics table indexes
        Schema::table('topics', function (Blueprint $table) {
            $table->index('course_id', 'topics_course_id_index');
            $table->index(['course_id', 'order'], 'topics_course_order_index');
        });

        // Courses table indexes
        Schema::table('courses', function (Blueprint $table) {
            $table->index('is_active', 'courses_active_index');
            $table->index('created_at', 'courses_created_at_index');
        });

        // Quiz attempts table indexes
        Schema::table('quiz_attempts', function (Blueprint $table) {
            $table->index('user_id', 'quiz_attempts_user_index');
            $table->index('topic_id', 'quiz_attempts_topic_index');
            $table->index(['user_id', 'topic_id'], 'quiz_attempts_user_topic_index');
            $table->index('created_at', 'quiz_attempts_created_at_index');
        });

        // User progress table indexes
        Schema::table('user_progress', function (Blueprint $table) {
            $table->index('user_id', 'user_progress_user_index');
            $table->index('topic_id', 'user_progress_topic_index');
            $table->index(['user_id', 'topic_id'], 'user_progress_user_topic_index');
        });

        // Payments table indexes
        Schema::table('payments', function (Blueprint $table) {
            $table->index('user_id', 'payments_user_index');
            $table->index('status', 'payments_status_index');
            $table->index(['user_id', 'status'], 'payments_user_status_index');
            $table->index('created_at', 'payments_created_at_index');
        });

        // Mistakes table indexes
        Schema::table('mistakes', function (Blueprint $table) {
            $table->index('user_id', 'mistakes_user_index');
            $table->index('question_id', 'mistakes_question_index');
            $table->index(['user_id', 'question_id'], 'mistakes_user_question_index');
            $table->index('created_at', 'mistakes_created_at_index');
        });

        // Notifications table indexes
        Schema::table('notifications', function (Blueprint $table) {
            $table->index('notifiable_id', 'notifications_notifiable_index');
            $table->index('read_at', 'notifications_read_at_index');
            $table->index(['notifiable_id', 'read_at'], 'notifications_notifiable_read_index');
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
            $table->dropIndex('users_email_index');
        });
    }
};
