<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mistakes', function (Blueprint $table) {
            $table->integer('attempt_count')->default(0)->after('submitted_answer');
            $table->index('attempt_count', 'idx_mistakes_attempt_count');
        });
    }

    public function down(): void
    {
        Schema::table('mistakes', function (Blueprint $table) {
            $table->dropIndex('idx_mistakes_attempt_count');
            $table->dropColumn('attempt_count');
        });
    }
};