<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_topic_source_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('topic_id')->constrained()->onDelete('cascade');
            $table->string('source');
            $table->json('completed_question_ids')->nullable();
            $table->json('correct_question_ids')->nullable();
            $table->unsignedInteger('completed_count')->default(0);
            $table->unsignedInteger('correct_count')->default(0);
            $table->unsignedInteger('total_questions')->default(0);
            $table->timestamp('last_attempt_date')->nullable();
            $table->timestamp('last_activity_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'topic_id', 'source'], 'user_topic_source_progress_unique');
            $table->index(['user_id', 'topic_id'], 'user_topic_source_progress_user_topic_idx');
            $table->index(['topic_id', 'source'], 'user_topic_source_progress_topic_source_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_topic_source_progress');
    }
};
