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
        Schema::create('study_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->date('plan_date');
            $table->integer('daily_goal')->default(0); // Questions goal for this day
            $table->integer('questions_completed')->default(0); // Actual questions completed
            $table->text('notes')->nullable(); // User notes for the day
            $table->json('topics')->nullable(); // Topics planned for the day
            $table->json('tasks')->nullable(); // Daily tasks checklist
            $table->string('color')->default('#3b82f6'); // Color code for the day
            $table->date('exam_date')->nullable(); // Target exam date
            $table->boolean('is_exam_day')->default(false); // Mark this day as exam day
            $table->boolean('is_committed')->default(false); // User committed to this plan
            $table->boolean('is_completed')->default(false); // Goal achieved
            $table->timestamps();
            
            // Ensure one plan per user per day
            $table->unique(['user_id', 'plan_date']);
            $table->index(['user_id', 'plan_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('study_plans');
    }
};
