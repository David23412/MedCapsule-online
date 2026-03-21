<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('questions', 'primary_concept_name')) {
            Schema::table('questions', function (Blueprint $table) {
                $table->dropColumn('primary_concept_name');
            });
        }

        if (Schema::hasColumn('mistakes', 'focus_concept_name')) {
            Schema::table('mistakes', function (Blueprint $table) {
                $table->dropColumn('focus_concept_name');
            });
        }
    }

    public function down(): void
    {
        if (!Schema::hasColumn('questions', 'primary_concept_name')) {
            Schema::table('questions', function (Blueprint $table) {
                $table->string('primary_concept_name')->nullable()->after('source');
            });
        }

        if (!Schema::hasColumn('mistakes', 'focus_concept_name')) {
            Schema::table('mistakes', function (Blueprint $table) {
                $table->string('focus_concept_name')->nullable()->after('correct_answer');
            });
        }
    }
};
