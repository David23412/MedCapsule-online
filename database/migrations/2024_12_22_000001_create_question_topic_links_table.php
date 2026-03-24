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
        Schema::create('question_topic_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('question_id')->constrained()->onDelete('cascade');
            $table->foreignId('topic_id')->constrained()->onDelete('cascade');
            
            // Prevent duplicate question-topic pairs
            $table->unique(['question_id', 'topic_id'], 'unique_question_topic');
            
            // Indexes for performance
            $table->index('question_id', 'idx_question_topic_links_question_id');
            $table->index('topic_id', 'idx_question_topic_links_topic_id');
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('question_topic_links');
    }
};
