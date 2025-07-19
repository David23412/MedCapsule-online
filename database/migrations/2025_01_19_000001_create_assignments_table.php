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
            $table->integer('duration_minutes')->default(60);
            $table->enum('status', ['active', 'completed', 'overdue', 'locked'])->default('active');
            $table->datetime('completed_at')->nullable();
            $table->integer('score')->nullable();
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['tutor_id', 'student_id'], 'idx_tutor_student');
            $table->index(['student_id', 'status'], 'idx_student_status');
            $table->index('deadline', 'idx_deadline');
            $table->index(['course_id', 'status'], 'idx_course_status');
            
            // Unique constraint to prevent duplicate assignments
            $table->unique(['tutor_id', 'student_id', 'topic_id'], 'unique_assignment');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assignments');
    }
};