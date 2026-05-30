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
            // REMOVED: user_id field - topics belong to courses, not users!
            // Individual user progress is tracked in user_topic_progress table
            $table->string('name'); // Name of the topic (quiz)
            $table->string('subject')->nullable(); // Display subject/category name (e.g., Anatomy)
            $table->string('case_type')->default('quiz'); // Type: quiz (regular topic), cases, or practical
            $table->integer('display_order')->default(0); // Order of topics within a course
            $table->boolean('is_locked')->default(false); // Topic locking status
            $table->boolean('is_exam')->default(false); // Whether this topic is an exam
            $table->unsignedInteger('cache_version')->default(1); // Cache version for invalidation
            $table->timestamps();

            // Add necessary indexes for optimized querying
            $table->index(['course_id', 'display_order']); // To query topics by course and order
            $table->index(['course_id', 'subject']); // To lock/unlock subjects within a course
            $table->index('course_id'); // For fast look-up of all topics by course
            $table->index('display_order'); // To speed up ordering within courses
            $table->index('case_type'); // For filtering by case type
            $table->index('is_locked'); // For topic locking queries
            $table->index('is_exam'); // For quick filtering of exam topics
            // REMOVED: Indexes for user-specific fields that no longer exist
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('topics');
    }
};
