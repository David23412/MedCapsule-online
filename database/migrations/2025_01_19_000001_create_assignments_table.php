<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tutor_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('student_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('topic_id')->constrained()->onDelete('cascade');
            $table->foreignId('course_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->text('description')->nullable();
            $table->datetime('deadline');
            $table->decimal('duration_minutes', 8, 2)->default(60.00);
            $table->string('assignment_mode')->default('complete_topic');
            $table->string('assigned_source')->nullable();
            $table->enum('status', ['active', 'completed', 'overdue', 'locked', 'expired'])->default('active');
            $table->datetime('completed_at')->nullable();
            $table->datetime('expired_at')->nullable();
            $table->datetime('started_at')->nullable();
            $table->integer('score')->nullable();
            $table->decimal('score_percentage', 5, 2)->nullable();
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['tutor_id', 'student_id'], 'idx_tutor_student');
            $table->index(['student_id', 'status'], 'idx_student_status');
            $table->index('deadline', 'idx_deadline');
            $table->index(['course_id', 'status'], 'idx_course_status');
            
            // REMOVED: Unique constraint to allow maximum flexibility for tutors
            // Tutors can now create as many assignments as they want for the same student/topic combination
        });

        // Create assignment_attempts table
        Schema::create('assignment_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assignment_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->unsignedBigInteger('student_id');
            $table->foreignId('topic_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('course_id')->nullable()->constrained()->onDelete('cascade');
            $table->integer('attempt_number')->default(1);
            $table->integer('total_questions')->default(0);
            $table->integer('correct_answers')->default(0);
            $table->integer('incorrect_answers')->default(0);
            $table->datetime('started_at');
            $table->datetime('completed_at')->nullable();
            $table->integer('score')->nullable();
            $table->decimal('score_percentage', 5, 2)->nullable();
            $table->integer('time_taken_seconds')->default(0);
            $table->json('answers')->nullable();
            $table->json('question_responses')->nullable();
            $table->string('assignment_mode')->nullable();
            $table->string('assigned_source')->nullable();
            $table->boolean('is_late_submission')->default(false);
            $table->text('notes')->nullable();
            $table->boolean('auto_submitted')->default(false);
            $table->enum('status', ['in_progress', 'completed', 'abandoned'])->default('in_progress');
            $table->timestamps();
            
            // Indexes
            $table->index(['assignment_id', 'user_id'], 'idx_assignment_user');
            $table->index(['user_id', 'status'], 'idx_user_status');
            $table->index(['student_id'], 'idx_student_id');
            $table->index('completed_at', 'idx_completed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assignment_attempts');
        Schema::dropIfExists('assignments');
    }
};