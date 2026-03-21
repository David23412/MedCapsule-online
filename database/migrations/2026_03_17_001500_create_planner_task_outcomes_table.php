<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('planner_task_outcomes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('study_plan_id')->nullable()->constrained('study_plans')->nullOnDelete();
            $table->foreignId('course_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('topic_id')->nullable()->constrained()->nullOnDelete();
            $table->date('plan_date');
            $table->string('task_id');
            $table->string('task_type');
            $table->string('title')->nullable();
            $table->string('study_mode')->nullable();
            $table->string('source')->nullable();
            $table->string('reason_code')->nullable();
            $table->string('planner_focus')->nullable();
            $table->unsignedInteger('allocated_minutes')->nullable();
            $table->unsignedInteger('total_daily_minutes')->nullable();
            $table->unsignedInteger('question_target')->nullable();
            $table->unsignedInteger('duration_minutes')->nullable();
            $table->boolean('is_completed')->default(false);
            $table->timestamp('first_presented_at')->nullable();
            $table->timestamp('last_presented_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->unsignedInteger('completion_latency_minutes')->nullable();
            $table->json('task_payload')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'plan_date', 'course_id', 'task_id'], 'planner_task_outcomes_unique');
            $table->index(['user_id', 'plan_date']);
            $table->index(['user_id', 'is_completed']);
            $table->index(['course_id', 'plan_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('planner_task_outcomes');
    }
};
