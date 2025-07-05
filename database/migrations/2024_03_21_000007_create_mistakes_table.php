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
            $table->timestamp('last_attempt_date')->nullable();
            $table->timestamps();

            // Ensure a user can't have duplicate mistakes for the same question
            $table->unique(['user_id', 'question_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mistakes');
    }
};