<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('questions', function (Blueprint $table) {
            $table->id(); // Auto-incrementing primary key
            $table->foreignId('topic_id')->constrained()->onDelete('cascade'); // Foreign key to topics table
            $table->text('question'); // Question text
            $table->text('explanation')->nullable(); // Optional explanation shown after answering
            $table->string('image_url')->nullable(); // Optional image for the question
            $table->string('source')->nullable(); // Source of the question (e.g., 'Assuit', 'Cairo', etc.)

            // Add multiple-choice options - nullable for written questions
            $table->string('option_a')->nullable();
            $table->string('option_b')->nullable();
            $table->string('option_c')->nullable();
            $table->string('option_d')->nullable();
            
            // Store correct answer (e.g., 'A', 'B', 'C', 'D' for multiple choice, or full text for written)
            $table->text('correct_answer');
            
            // Add question_type field and default existing questions to 'multiple_choice'
            $table->string('question_type')->default('multiple_choice');
            
            // Add field for alternative correct answers (TEXT field to store plain text)
            $table->text('alternative_answers')->nullable();

            // Add similarity threshold for written answers
            $table->float('similarity_threshold')->default(0.8);

            // Correct answer and attempt tracking
            $table->integer('total_attempts')->default(0);
            $table->integer('correct_attempts')->default(0);
            $table->timestamp('last_attempted_at')->nullable();

            $table->timestamps(); // Created_at and Updated_at timestamps
            
            // Indexes for better performance
            $table->index('topic_id'); // Fast look-up for questions by topic
            $table->index(['topic_id', 'last_attempted_at']); // For sorting by the most recently attempted question
            $table->index(['source', 'question_type']); // For filtering questions by source and type
            $table->index(['topic_id', 'question_type']); // For filtering questions by topic and type
            $table->index(['total_attempts', 'correct_attempts']); // For difficulty analysis
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('questions');
    }
};
