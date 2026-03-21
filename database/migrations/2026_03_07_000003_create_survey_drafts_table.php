<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('survey_drafts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('survey_key')->default(config('free_course_survey.survey_key', 'free_course_unlock_v1'));
            $table->json('answers')->nullable();
            $table->unsignedTinyInteger('current_step')->default(1);
            $table->string('redirect_to')->nullable();
            $table->timestamp('last_saved_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'survey_key']);
            $table->index(['survey_key', 'last_saved_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('survey_drafts');
    }
};
