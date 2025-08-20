<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('videos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('topic_id')->constrained()->onDelete('cascade');
            $table->string('youtube_video_id');
            $table->string('title');
            $table->text('description')->nullable();
            $table->integer('duration')->nullable(); // in seconds
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            // Performance indexes
            $table->index('topic_id');
            $table->index(['topic_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::disableForeignKeyConstraints();
        // Some environments may have a legacy table referencing videos
        if (Schema::hasTable('video_user_progress')) {
            Schema::drop('video_user_progress');
        }
        Schema::dropIfExists('videos');
        Schema::enableForeignKeyConstraints();
    }
};