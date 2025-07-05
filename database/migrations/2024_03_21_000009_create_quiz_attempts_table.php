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
            $table->foreignId('topic_id')->constrained()->onDelete('cascade');
            $table->string('quiz_type')->default('normal'); // normal, random, etc.
            $table->string('study_mode')->default('quiz'); // quiz or tutor
            $table->string('source')->nullable(); // Which source this attempt is for (Assuit, Cairo, etc.)
            
            // Progress tracking
            $table->unsignedInteger('score'); // Number of correct answers
            $table->unsignedInteger('total_questions'); // Total questions in this attempt
            $table->decimal('percentage_grade', 5, 2); // Percentage score (0-100)
            $table->unsignedInteger('duration_seconds'); // Duration in seconds
            
            // Mistake tracking (as JSON)
            $table->json('missed_questions')->nullable(); // Array of question IDs that were wrong
            $table->json('question_data')->nullable(); // Detailed attempt data if needed for review
            
            // Add indexes for efficient querying
            $table->index(['user_id', 'topic_id']);
            $table->index(['source', 'study_mode']);
            $table->index('created_at'); // For getting latest attempts efficiently
            
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('quiz_attempts');
    }
}; 