<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Create comprehensive security logs table
        Schema::create('session_security_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('event_type', 50); // login, logout, suspicious_activity, session_hijack, failed_login
            $table->string('ip_address', 45);
            $table->text('user_agent')->nullable();
            $table->string('device_fingerprint')->nullable();
            $table->json('security_data')->nullable(); // Risk analysis, flags, metadata
            $table->enum('risk_level', ['low', 'medium', 'high', 'critical'])->default('low');
            $table->boolean('action_taken')->default(false);
            $table->timestamps();
            
            // Performance indexes for fast queries
            $table->index(['user_id', 'event_type']);
            $table->index(['created_at']);
            $table->index(['risk_level']);
            $table->index(['ip_address']);
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('session_security_logs');
    }
};
