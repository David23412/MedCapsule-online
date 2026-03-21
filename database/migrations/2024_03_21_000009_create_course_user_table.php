<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('course_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('course_id')->constrained()->onDelete('cascade');
            $table->boolean('is_enrolled')->default(true);
            $table->string('access_level', 20)->nullable();
            $table->timestamp('enrolled_at')->nullable();
            $table->date('planner_exam_date')->nullable();
            $table->unsignedTinyInteger('planner_target_score')->nullable();
            $table->unsignedSmallInteger('planner_daily_study_minutes')->nullable();
            $table->string('planner_focus', 40)->nullable();
            $table->string('planner_preferred_source')->nullable();
            $table->string('planner_repair_mode', 20)->nullable();
            $table->string('planner_classic_repair_difficulty', 20)->nullable();
            $table->timestamps();

            // Indexes for better performance
            $table->unique(['user_id', 'course_id']);
            $table->index(['user_id', 'is_enrolled']);
            $table->index(['course_id', 'is_enrolled', 'user_id'], 'idx_course_user_course_enrolled_user');
            $table->index(['course_id', 'access_level'], 'idx_course_user_access_level');
            $table->index(['course_id', 'planner_exam_date'], 'idx_course_user_planner_exam_date');
            $table->index(['course_id', 'planner_focus'], 'idx_course_user_planner_focus');
            $table->index(['course_id', 'planner_repair_mode'], 'idx_course_user_planner_repair_mode');
            $table->index('enrolled_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_user');
    }
};
