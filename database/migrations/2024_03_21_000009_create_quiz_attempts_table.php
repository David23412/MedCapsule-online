<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('quiz_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('topic_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('assignment_id')->nullable()->constrained()->onDelete('cascade'); // 🔥 CONSOLIDATED
            $table->string('quiz_type')->default('normal'); // normal, random, etc.
            $table->string('study_mode')->nullable(); // quiz or tutor, now nullable
            $table->string('source')->nullable(); // Which source this attempt is for (Assuit, Cairo, etc.)
            
            // Progress tracking
            $table->unsignedInteger('score'); // Number of correct answers
            $table->unsignedInteger('total_questions'); // Total questions in this attempt
            $table->decimal('percentage_grade', 5, 2); // Percentage score (0-100)
            $table->unsignedInteger('duration_seconds'); // Duration in seconds
            
            // Mistake tracking (as JSON)
            $table->json('question_data')->nullable(); // Detailed attempt data if needed for review
            $table->integer('correct_answers')->nullable(); // Count of correct answers (same as score)
            
            // 🚀 LEAN INDEXES: Only essential ones (detailed indexes in nuclear_performance_indexing)
            $table->index(['user_id', 'topic_id']); // Basic user-topic queries
            $table->index('created_at'); // Chronological ordering
            $table->string('attempt_id')->nullable(); // Unique attempt identifier for tracking

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('quiz_attempts');
    }
};
