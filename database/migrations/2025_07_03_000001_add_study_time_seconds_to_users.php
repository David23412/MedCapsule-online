<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'study_time_seconds')) {
                $table->unsignedBigInteger('study_time_seconds')->default(0);
            }
            if (!Schema::hasColumn('users', 'study_streak_days')) {
                $table->integer('study_streak_days')->default(0);
            }
            if (!Schema::hasColumn('users', 'last_study_date')) {
                $table->timestamp('last_study_date')->nullable();
            }
            if (!Schema::hasColumn('users', 'correct_answers_count')) {
                $table->integer('correct_answers_count')->default(0);
            }
            if (!Schema::hasColumn('users', 'total_questions_attempted')) {
                $table->integer('total_questions_attempted')->default(0);
            }
            if (!Schema::hasColumn('users', 'xp')) {
                $table->integer('xp')->default(0);
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'study_time_seconds',
                'study_streak_days',
                'last_study_date',
                'correct_answers_count',
                'total_questions_attempted',
                'xp'
            ]);
        });
    }
}; 