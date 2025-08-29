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
        Schema::table('courses', function (Blueprint $table) {
            // Drop the index for is_active first
            $table->dropIndex('idx_courses_is_active');

            // Drop the cache_version and is_active columns
            $table->dropColumn(['cache_version', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            // Add back the columns
            $table->unsignedInteger('cache_version')->default(1);
            $table->boolean('is_active')->default(true);

            // Add back the index
            $table->index('is_active', 'idx_courses_is_active');
        });
    }
};
