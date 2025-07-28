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
        Schema::create('user_stats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->integer('total_quizzes_taken')->default(0);
            $table->integer('total_questions_attempted')->default(0);
            $table->integer('correct_answers_count')->default(0);
            $table->integer('study_streak_days')->default(0);
            $table->timestamp('last_study_date')->nullable();
            $table->integer('total_xp_earned')->default(0);
            $table->integer('current_level')->default(1);
            $table->timestamps();
            
            $table->unique('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_stats');
    }
};
