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
            // SSOT: Only enrolled_at timestamp - no boolean needed
            $table->timestamp('enrolled_at')->nullable()->comment('SSOT: NULL = not enrolled, timestamp = enrolled');
            $table->timestamps();

            // Indexes for better performance using SSOT
            $table->unique(['user_id', 'course_id']);
            $table->index(['user_id', 'enrolled_at']); // Index on SSOT field
            $table->index(['course_id', 'enrolled_at']); // For course enrollment queries
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_user');
    }
}; 