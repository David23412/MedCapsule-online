<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_topic_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('topic_id')->constrained()->onDelete('cascade');
            $table->float('percentage_grade')->nullable();
            $table->timestamp('last_attempt_date')->nullable();
            $table->timestamps();

            // Add unique constraint to prevent duplicate records
            $table->unique(['user_id', 'topic_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_topic_progress');
    }
}; 