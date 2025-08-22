<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assignment_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assignment_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->timestamp('extended_deadline')->nullable(); // Individual deadline extensions
            $table->timestamps();

            // Indexes for better performance
            $table->unique(['assignment_id', 'user_id']);
            $table->index(['user_id']);
            $table->index(['assignment_id']);
            $table->index(['extended_deadline']); // Index for deadline queries
        });
    }

    public function down(): void
    {
        // ✅ FIXED: Remove foreign key constraint disabling - maintain data integrity
        Schema::dropIfExists('assignment_user');
        Schema::dropIfExists('assignment_users');
    }
};