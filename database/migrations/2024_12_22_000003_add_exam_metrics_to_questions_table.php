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
        Schema::table('questions', function (Blueprint $table) {
            // Add exam_appearances_count to track how many exams this question appears in
            $table->unsignedInteger('exam_appearances_count')->default(0)->after('concept');
            
            // Add indexes for performance
            $table->index('exam_appearances_count', 'idx_questions_exam_appearances');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->dropIndex('idx_questions_exam_appearances');
            $table->dropColumn(['exam_appearances_count']);
        });
    }
};
