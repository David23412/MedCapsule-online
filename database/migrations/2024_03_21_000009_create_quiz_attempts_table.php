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
            $table->string('session_type', 32)->nullable();
            $table->string('source')->nullable(); // Which source this attempt is for (Assuit, Cairo, etc.)
            
            // Progress tracking
            $table->unsignedInteger('score'); // Number of correct answers
            $table->unsignedInteger('total_questions'); // Total questions in this attempt
            $table->decimal('percentage_grade', 5, 2); // Percentage score (0-100)
            $table->unsignedInteger('duration_seconds'); // Duration in seconds
            
            // Mistake tracking (as JSON)
            $table->json('question_data')->nullable(); // Detailed attempt data if needed for review
            
            // 🚀 LEAN INDEXES: Only essential ones (detailed indexes in nuclear_performance_indexing)
            $table->index(['user_id', 'topic_id']); // Basic user-topic queries
            $table->index('created_at'); // Chronological ordering
            $table->index(['user_id', 'created_at', 'id'], 'idx_quiz_attempts_user_created_id'); // Recent-attempt scans per user
            $table->index(['user_id', 'topic_id', 'study_mode', 'source', 'id'], 'idx_qa_user_topic_mode_source_id'); // Latest-source progress lookups
            $table->unsignedBigInteger('attempt_id')->nullable(); // Numeric group identifier for multi-source attempts

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('quiz_attempts');
    }
};
