<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('course_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('course_id')->constrained()->onDelete('cascade');
            $table->enum('enrollment_status', ['active', 'inactive'])->default('inactive');
            $table->timestamp('enrolled_at')->nullable();
            $table->timestamps();

            // Indexes for better performance
            $table->unique(['user_id', 'course_id']);
            $table->index(['user_id', 'enrollment_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_user');
    }
}; 