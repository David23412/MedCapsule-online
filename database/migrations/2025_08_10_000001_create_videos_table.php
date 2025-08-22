<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Create videos table
        Schema::create('videos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('topic_id')->constrained()->onDelete('cascade');
            $table->string('youtube_video_id');
            $table->string('title');
            $table->integer('duration')->nullable(); // in seconds
            $table->timestamps();

            // Performance indexes for videos
            $table->index('topic_id');
            $table->index('youtube_video_id');
        });

        // Create video progress pivot table
        Schema::create('video_user_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('video_id')->constrained()->onDelete('cascade');
            $table->foreignId('topic_id')->constrained()->onDelete('cascade');
            $table->decimal('current_time', 10, 2)->default(0); // Current playback time in seconds
            $table->decimal('duration', 10, 2)->default(0); // Total video duration in seconds
            $table->decimal('percentage', 5, 2)->default(0); // Percentage watched (0-100)
            $table->timestamp('last_watched_at')->nullable();
            $table->timestamps();
            
            // Ensure one progress record per user per video
            $table->unique(['user_id', 'video_id', 'topic_id'], 'user_video_topic_unique');
            
            // Performance indexes for video progress
            $table->index(['user_id', 'topic_id']);
            $table->index(['user_id', 'video_id']);
            $table->index(['user_id', 'percentage']); // Index for completion queries
            $table->index(['topic_id', 'percentage']);
            $table->index('last_watched_at');
        });
    }

    public function down(): void
    {
        // ✅ FIXED: Remove foreign key constraint disabling - maintain data integrity
        Schema::dropIfExists('video_user_progress');
        Schema::dropIfExists('videos');
    }
};
