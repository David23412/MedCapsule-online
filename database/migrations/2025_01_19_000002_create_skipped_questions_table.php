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
        Schema::create('skipped_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('question_id')->constrained()->onDelete('cascade');
            $table->datetime('skipped_at')->default(now());
            $table->enum('session_type', ['learn_mistakes', 'quiz', 'review'])->default('learn_mistakes');
            
            // Indexes for performance
            $table->index(['user_id', 'session_type'], 'idx_user_session');
            $table->index(['question_id', 'user_id'], 'idx_question_user');
            
            // Unique constraint to prevent duplicate skips
            $table->unique(['user_id', 'question_id', 'session_type'], 'unique_skip');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('skipped_questions');
    }
};