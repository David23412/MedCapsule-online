<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('mistake_review_events')) {
            return;
        }

        Schema::create('mistake_review_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mistake_id')->constrained('mistakes')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('question_id')->constrained('questions')->cascadeOnDelete();
            $table->string('review_mode', 32);
            $table->string('event_type', 32)->default('review');
            $table->unsignedTinyInteger('rating');
            $table->boolean('is_correct');
            $table->string('confidence_level', 16)->nullable();
            $table->decimal('confidence_weight', 5, 2)->nullable();
            $table->unsignedInteger('response_seconds')->nullable();
            $table->string('first_selected_answer', 1000)->nullable();
            $table->string('final_selected_answer', 1000)->nullable();
            $table->unsignedTinyInteger('answer_change_count')->default(0);
            $table->unsignedInteger('time_to_first_selection')->nullable();
            $table->string('first_confidence_level', 32)->nullable();
            $table->unsignedTinyInteger('confidence_change_count')->default(0);
            $table->decimal('elapsed_days_since_last_review', 8, 4)->nullable();
            $table->decimal('prior_difficulty', 5, 2)->nullable();
            $table->decimal('prior_stability', 8, 2)->nullable();
            $table->decimal('prior_retrievability', 6, 4)->nullable();
            $table->unsignedInteger('prior_interval_days')->nullable();
            $table->timestamp('prior_next_review_at')->nullable();
            $table->decimal('resulting_difficulty', 5, 2)->nullable();
            $table->decimal('resulting_stability', 8, 2)->nullable();
            $table->decimal('resulting_retrievability', 6, 4)->nullable();
            $table->unsignedInteger('resulting_interval_days')->nullable();
            $table->timestamp('resulting_next_review_at')->nullable();
            $table->decimal('target_retention', 6, 4)->nullable();
            $table->decimal('decay_base', 6, 4)->nullable();
            $table->json('calibration_snapshot')->nullable();
            $table->timestamp('reviewed_at');
            $table->timestamps();

            $table->index(['review_mode', 'event_type', 'reviewed_at'], 'mistake_review_events_mode_type_reviewed_idx');
            $table->index(['mistake_id', 'reviewed_at']);
            $table->index(['user_id', 'review_mode', 'event_type', 'reviewed_at'], 'mistake_review_events_user_mode_idx');
            $table->index(['review_mode', 'event_type', 'confidence_level'], 'mistake_review_events_mode_conf_idx');
            $table->index(['question_id', 'reviewed_at'], 'mistake_review_events_question_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mistake_review_events');
    }
};
