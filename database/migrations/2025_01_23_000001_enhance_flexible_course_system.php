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
        // Add type column to courses table if it doesn't exist
        if (!Schema::hasColumn('courses', 'type')) {
            Schema::table('courses', function (Blueprint $table) {
                $table->enum('type', ['medical', 'custom'])->default('medical')->after('description');
            });
        }

        // Add source priority field for source ordering in topics table
        Schema::table('topics', function (Blueprint $table) {
            // Add source priority field for source ordering if it doesn't exist
            if (!Schema::hasColumn('topics', 'source_priorities')) {
                $table->json('source_priorities')->nullable()->comment('JSON array of source priorities');
            }
        });

        // Add comprehensive indexes for topics if they don't exist
        try {
            Schema::table('topics', function (Blueprint $table) {
                $table->index('case_type', 'idx_topics_categorization');
                $table->index(['course_id', 'case_type'], 'idx_topics_course_categorization');
            });
        } catch (Exception $e) {
            // Indexes might already exist, continue
        }

        // Create source_priorities table for managing course-level source priorities
        if (!Schema::hasTable('source_priorities')) {
            Schema::create('source_priorities', function (Blueprint $table) {
                $table->id();
                $table->foreignId('course_id')->constrained()->onDelete('cascade');
                $table->string('source_name');
                $table->integer('priority_order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                
                // Indexes for performance
                $table->index(['course_id', 'priority_order'], 'idx_source_priorities_course_order');
                $table->index(['course_id', 'is_active'], 'idx_source_priorities_course_active');
                $table->unique(['course_id', 'source_name'], 'unique_course_source');
            });
        }

        // Create assignment_attempts table for tracking assignment quiz attempts
        if (!Schema::hasTable('assignment_attempts')) {
            Schema::create('assignment_attempts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('assignment_id')->constrained()->onDelete('cascade');
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->string('source')->nullable()->comment('Source used for this attempt');
                $table->integer('questions_answered')->default(0);
                $table->integer('correct_answers')->default(0);
                $table->decimal('score_percentage', 5, 2)->default(0);
                $table->integer('time_spent_seconds')->default(0);
                $table->timestamp('started_at');
                $table->timestamp('completed_at')->nullable();
                $table->boolean('auto_submitted')->default(false)->comment('Whether this was auto-submitted due to time expiry');
                $table->json('answers')->nullable()->comment('JSON of question_id => answer pairs');
                $table->timestamps();
                
                // Indexes for performance
                $table->index(['assignment_id', 'user_id'], 'idx_assignment_attempts_assignment_user');
                $table->index(['user_id', 'completed_at'], 'idx_assignment_attempts_user_completed');
                $table->index('source', 'idx_assignment_attempts_source');
                $table->index('auto_submitted', 'idx_assignment_attempts_auto_submitted');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop new tables
        Schema::dropIfExists('assignment_attempts');
        Schema::dropIfExists('source_priorities');
        
        // Revert courses table changes
        if (Schema::hasColumn('courses', 'type')) {
            Schema::table('courses', function (Blueprint $table) {
                $table->dropColumn('type');
            });
        }

        // Revert topics table changes
        Schema::table('topics', function (Blueprint $table) {
            if (Schema::hasColumn('topics', 'source_priorities')) {
                $table->dropColumn('source_priorities');
            }
        });
        
        // Drop indexes if they exist
        try {
            Schema::table('topics', function (Blueprint $table) {
                $table->dropIndex('idx_topics_categorization');
                $table->dropIndex('idx_topics_course_categorization');
            });
        } catch (Exception $e) {
            // Indexes might not exist, continue
        }
    }
};