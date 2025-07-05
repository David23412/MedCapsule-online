<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add indexes to quiz_attempts table
        Schema::table('quiz_attempts', function (Blueprint $table) {
            // Index for efficient querying of user's attempts in a topic with specific mode
            $table->index(['user_id', 'topic_id', 'study_mode'], 'idx_user_topic_mode');
            
            // Index for source-specific queries
            $table->index(['user_id', 'source'], 'idx_user_source');
            
            // Index for performance queries
            $table->index(['user_id', 'percentage_grade'], 'idx_user_performance');
        });

        // Add indexes to mistakes table
        Schema::table('mistakes', function (Blueprint $table) {
            // Index for tracking user's progress and mastery
            $table->index(['user_id', 'correct_streak'], 'idx_user_streak');
            
            // Index for recent mistakes
            $table->index(['user_id', 'last_attempt_date'], 'idx_user_last_attempt');
        });
    }

    public function down(): void
    {
        Schema::table('quiz_attempts', function (Blueprint $table) {
            $table->dropIndex('idx_user_topic_mode');
            $table->dropIndex('idx_user_source');
            $table->dropIndex('idx_user_performance');
        });

        Schema::table('mistakes', function (Blueprint $table) {
            $table->dropIndex('idx_user_streak');
            $table->dropIndex('idx_user_last_attempt');
        });
    }
}; 