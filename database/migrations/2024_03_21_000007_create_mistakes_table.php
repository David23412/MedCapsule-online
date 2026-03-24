<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mistakes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('question_id')->constrained()->onDelete('cascade');
            $table->string('submitted_answer')->nullable(); // User's wrong answer (null if skipped)
            
            $table->integer('correct_streak')->default(0); // # of consecutive correct answers
            $table->integer('quick_correct_count')->default(0); // Count of correct answers in quick mode
            $table->integer('quiz_correct_count')->default(0); // Count of correct answers in quiz mode
            $table->unsignedInteger('review_count')->default(0);
            $table->unsignedInteger('lapse_count')->default(0);
            $table->unsignedInteger('interval_days')->default(0);
            $table->decimal('ease_factor', 4, 2)->default(2.30);
            $table->decimal('difficulty', 5, 2)->default(0.45);
            $table->decimal('difficulty_baseline', 5, 2)->nullable();
            $table->decimal('stability', 8, 2)->default(0.50);
            $table->decimal('retrievability', 5, 4)->default(0.9000);
            $table->unsignedInteger('skip_count')->default(0);
            $table->unsignedInteger('consecutive_skip_count')->default(0);
            $table->unsignedInteger('last_response_seconds')->nullable();
            $table->unsignedTinyInteger('last_review_rating')->nullable();
            $table->string('last_review_confidence', 16)->nullable();
            $table->decimal('hesitation_score', 6, 4)->default(0.0000);
            $table->string('learning_state', 32)->nullable();
            $table->unsignedTinyInteger('same_day_relearn_step')->default(0);
            $table->unsignedTinyInteger('same_day_relearn_count')->default(0);
            $table->boolean('mastered')->default(false); // Whether the question has been mastered
            $table->boolean('skipped')->default(false); // Whether the question was skipped
            $table->timestamp('last_attempt_date')->nullable();
            $table->timestamp('last_reviewed_at')->nullable();
            $table->timestamp('last_skipped_at')->nullable();
            $table->timestamp('skip_until')->nullable();
            $table->timestamp('next_review_at')->nullable();
            $table->timestamp('mastered_at')->nullable();
            $table->timestamps();

            // Ensure a user can't have duplicate mistakes for the same question
            $table->unique(['user_id', 'question_id']);
            
            // Add indexes for efficient querying
            $table->index(['user_id', 'correct_streak'], 'idx_user_streak');
            $table->index(['user_id', 'last_attempt_date'], 'idx_user_last_attempt');
            $table->index(['user_id', 'mastered', 'skipped', 'next_review_at'], 'idx_user_review_queue');
            $table->index(['user_id', 'skip_until'], 'idx_user_skip_until');
            $table->index(['user_id', 'learning_state'], 'idx_user_learning_state');
            $table->index(['user_id', 'retrievability'], 'idx_user_retrievability');
            $table->index('skipped');
        });

        Schema::create('user_mistake_mastery', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('question_id')->constrained()->onDelete('cascade');
            $table->foreignId('course_id')->constrained()->onDelete('cascade');
            $table->unsignedInteger('consecutive_correct')->default(0);
            $table->timestamp('graduated_at')->nullable();
            $table->string('graduation_mode', 20)->nullable(); // Tracks if graduated in easy/hard mode
            $table->timestamp('last_attempt_at')->nullable();
            $table->string('last_attempt_result', 16)->nullable();
            $table->unsignedInteger('total_attempts')->default(0);
            $table->timestamps();

            $table->unique(['user_id', 'question_id']);
            $table->index(['user_id', 'course_id', 'graduated_at'], 'idx_umm_user_course_graduated');
            $table->index(['user_id', 'last_attempt_at'], 'idx_umm_user_last_attempt');
            $table->index(['course_id', 'graduated_at'], 'idx_umm_course_graduated');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_mistake_mastery');
        Schema::dropIfExists('mistakes');
    }
};
