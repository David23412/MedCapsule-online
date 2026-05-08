<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('grading_diagnostics')) {
            return;
        }

        Schema::create('grading_diagnostics', function (Blueprint $table) {
            $table->id();
            $table->string('grader_version');
            $table->unsignedBigInteger('question_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('submitted_answer_hash', 64)->nullable();
            $table->boolean('old_result')->nullable();
            $table->boolean('new_result')->nullable();
            $table->boolean('disagreement')->default(false);
            $table->string('reason_code');
            $table->json('diagnostics')->nullable();
            $table->timestamps();

            $table->index(['question_id', 'disagreement', 'created_at'], 'grading_diag_question_disagreement_created_idx');
            $table->index(['grader_version', 'disagreement'], 'grading_diag_version_disagreement_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grading_diagnostics');
    }
};
