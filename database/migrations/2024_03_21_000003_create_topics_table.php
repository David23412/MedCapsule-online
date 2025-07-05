<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('topics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->onDelete('cascade'); // Foreign key for course
            $table->foreignId('user_id')->nullable()->constrained(); // Add this line to track per-user progress
            $table->string('name'); // Name of the topic (quiz)
            $table->text('description')->nullable(); // Description for the topic (optional)
            $table->string('case_type')->default('quiz'); // Type: quiz (regular topic), cases, or practical
            $table->integer('display_order')->default(0); // Order of topics within a course
            $table->float('percentage_grade')->nullable(); // Track percentage grade for the topic
            $table->timestamp('last_attempt_date')->nullable(); // Store the last attempt date for review
            $table->timestamps();

            // Add necessary indexes for optimized querying
            $table->index(['course_id', 'display_order']); // To query topics by course and order
            $table->index('course_id'); // For fast look-up of all topics by course
            $table->index('display_order'); // To speed up ordering within courses
            $table->index('case_type'); // For filtering by case type
            $table->index('percentage_grade'); // For filtering by performance level
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('topics');
    }
};