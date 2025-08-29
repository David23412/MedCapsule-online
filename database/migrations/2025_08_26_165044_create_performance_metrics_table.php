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
        Schema::create('performance_metrics', function (Blueprint $table) {
            $table->id();
            $table->string('operation'); // e.g., 'system_boot', 'query_execution', etc.
            $table->decimal('duration_ms', 8, 2); // duration in milliseconds
            $table->decimal('memory_usage_mb', 8, 2); // current memory usage in MB
            $table->decimal('peak_memory_mb', 8, 2); // peak memory usage in MB
            $table->json('context')->nullable(); // additional context data as JSON
            $table->json('additional_data')->nullable(); // additional performance data as JSON
            $table->timestamp('completed_at')->nullable(); // when the operation completed
            $table->timestamps();

            // Add indexes for performance
            $table->index(['operation', 'created_at']);
            $table->index('completed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('performance_metrics');
    }
};
