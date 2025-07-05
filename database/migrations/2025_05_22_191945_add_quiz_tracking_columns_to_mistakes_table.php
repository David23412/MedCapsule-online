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
        Schema::table('mistakes', function (Blueprint $table) {
            // Add quick_correct_count column if it doesn't exist
            if (!Schema::hasColumn('mistakes', 'quick_correct_count')) {
                $table->integer('quick_correct_count')->default(0)->after('correct_streak');
            }
            
            // Add quiz_correct_count column if it doesn't exist
            if (!Schema::hasColumn('mistakes', 'quiz_correct_count')) {
                $table->integer('quiz_correct_count')->default(0)->after('quick_correct_count');
            }
            
            // Add mastered column if it doesn't exist
            if (!Schema::hasColumn('mistakes', 'mastered')) {
                $table->boolean('mastered')->default(false)->after('quiz_correct_count');
            }
        });

        Schema::table('quiz_attempts', function (Blueprint $table) {
            // Remove redundant column as this data is better tracked in question_data
            $table->dropColumn('missed_questions');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mistakes', function (Blueprint $table) {
            // Remove columns if they exist
            if (Schema::hasColumn('mistakes', 'quick_correct_count')) {
                $table->dropColumn('quick_correct_count');
            }
            
            if (Schema::hasColumn('mistakes', 'quiz_correct_count')) {
                $table->dropColumn('quiz_correct_count');
            }
            
            if (Schema::hasColumn('mistakes', 'mastered')) {
                $table->dropColumn('mastered');
            }
        });

        Schema::table('quiz_attempts', function (Blueprint $table) {
            // Add back the column in case of rollback
            $table->json('missed_questions')->nullable();
        });
    }
};
