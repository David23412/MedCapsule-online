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
            $table->string('submitted_answer'); // User's wrong answer
            $table->text('question_text'); // Stores question text directly
            $table->string('correct_answer'); // Correct answer for review
            $table->string('image_path')->nullable(); // Image if attached to question
            $table->integer('correct_streak')->default(0); // # of consecutive correct answers
            $table->integer('quick_correct_count')->default(0); // Count of correct answers in quick mode
            $table->integer('quiz_correct_count')->default(0); // Count of correct answers in quiz mode
            $table->boolean('mastered')->default(false); // Whether the question has been mastered
            $table->timestamp('last_attempt_date')->nullable();
            $table->timestamps();

            // Ensure a user can't have duplicate mistakes for the same question
            $table->unique(['user_id', 'question_id']);
            
            // Add indexes for efficient querying
            $table->index(['user_id', 'correct_streak'], 'idx_user_streak');
            $table->index(['user_id', 'last_attempt_date'], 'idx_user_last_attempt');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mistakes');
    }
};