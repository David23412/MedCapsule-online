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
            $table->text('description')->nullable(); // Description for the topic (optional)
            $table->string('case_type')->default('quiz'); // Type: quiz (regular topic), cases, or practical
            $table->integer('display_order')->default(0); // Order of topics within a course
            // REMOVED: User-specific fields (mastered, percentage_grade, last_attempt_date)
            // These are tracked per-user in user_topic_progress table
            $table->boolean('is_locked')->default(false); // Topic locking status
            $table->foreignId('locked_by_tutor_id')->nullable()->constrained('users')->onDelete('set null'); // Tutor who locked the topic
            $table->unsignedInteger('cache_version')->default(1); // Cache version for invalidation
            $table->timestamps();

            // Add necessary indexes for optimized querying
            $table->index(['course_id', 'display_order']); // To query topics by course and order
            $table->index('course_id'); // For fast look-up of all topics by course
            $table->index('display_order'); // To speed up ordering within courses
            $table->index('case_type'); // For filtering by case type
            $table->index(['is_locked', 'locked_by_tutor_id'], 'idx_topics_locked'); // For topic locking queries
            // REMOVED: Indexes for user-specific fields that no longer exist
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('topics');
    }
};