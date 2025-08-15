<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('video_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('video_id')->constrained()->onDelete('cascade');
            $table->integer('watch_time_seconds')->default(0);
            $table->integer('total_duration_seconds')->nullable();
            // SSOT: Only progress_percentage - completed is derived (>= 90%)
            $table->decimal('progress_percentage', 5, 2)->default(0)->comment('SSOT: >= 90% = completed');
            $table->timestamp('last_watched_at')->nullable();
            $table->timestamp('completed_at')->nullable()->comment('When video was first completed (>= 90%)');
            $table->timestamps();
            
            $table->unique(['user_id', 'video_id']);
            $table->index(['user_id', 'progress_percentage']); // SSOT index
            $table->index(['user_id', 'completed_at']); // For completed videos queries
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('video_progress');
    }
};