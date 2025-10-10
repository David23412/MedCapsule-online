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
            $table->string('operation');
            $table->decimal('duration_ms', 10, 2)->default(0);
            $table->decimal('memory_usage_mb', 10, 2)->default(0);
            $table->decimal('peak_memory_mb', 10, 2)->default(0);
            $table->json('context')->nullable();
            $table->json('additional_data')->nullable();
            $table->timestamps();
            $table->index(['operation', 'created_at']);
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
