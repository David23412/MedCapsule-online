<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('grading_evaluations')) {
            return;
        }

        Schema::create('grading_evaluations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->text('student_answer');
            $table->text('correct_answer');
            $table->string('subject')->nullable();
            $table->decimal('similarity_score', 8, 4)->default(0);
            $table->decimal('confidence_score', 8, 4)->default(0);
            $table->unsignedInteger('processing_time_microseconds')->default(0);
            $table->json('metadata')->nullable();
            $table->json('feedback')->nullable();
            $table->decimal('human_score', 8, 4)->nullable();
            $table->string('algorithm_version')->nullable();
            $table->boolean('cache_used')->default(false);
            $table->text('error_message')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamps();

            $table->index('subject');
            $table->index('similarity_score');
            $table->index('created_at');
            $table->index(['subject', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grading_evaluations');
    }
};
