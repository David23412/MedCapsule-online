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
        Schema::create('quiz_performance_metrics', function (Blueprint $table) {
            $table->id();
            $table->string('operation', 100)->index();
            $table->decimal('duration_ms', 10, 2);
            $table->decimal('memory_used_mb', 8, 2);
            $table->integer('queries_executed')->default(0);
            $table->unsignedBigInteger('topic_id')->nullable()->index();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->boolean('success')->default(true)->index();
            $table->text('error_message')->nullable();
            $table->json('additional_data')->nullable();
            $table->timestamps();

            // Indexes for performance
            $table->index(['operation', 'created_at']);
            $table->index(['success', 'created_at']);
            $table->index(['duration_ms', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quiz_performance_metrics');
    }
};