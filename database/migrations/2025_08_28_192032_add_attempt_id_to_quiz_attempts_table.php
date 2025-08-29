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
        Schema::table('quiz_attempts', function (Blueprint $table) {
            // Add attempt_id column for grouping multiple sources from same quiz
            $table->string('attempt_id', 255)->nullable()->after('assignment_id');

            // Add index for fast GROUP BY queries
            $table->index('attempt_id');

            // Add composite index for common queries (user + attempt_id)
            $table->index(['user_id', 'attempt_id']);

            // Add composite index for topic-based queries (topic + attempt_id)
            $table->index(['topic_id', 'attempt_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quiz_attempts', function (Blueprint $table) {
            // Drop the column first, which will automatically drop related indexes
            $table->dropColumn('attempt_id');
        });
    }
};
