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
        Schema::create('course_access_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('course_id')->constrained()->onDelete('cascade');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->datetime('requested_at');
            $table->datetime('processed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['user_id', 'course_id'], 'idx_user_course');
            $table->index(['status', 'requested_at'], 'idx_status_requested');
            $table->index('course_id', 'idx_course_requests');
            
            // Prevent duplicate requests
            $table->unique(['user_id', 'course_id'], 'unique_user_course_request');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('course_access_requests');
    }
};