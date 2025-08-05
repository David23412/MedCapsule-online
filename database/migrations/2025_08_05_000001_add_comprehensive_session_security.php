<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add all session security fields to users table
        Schema::table('users', function (Blueprint $table) {
            // Core session management
            $table->string('active_session_token')->nullable()->after('remember_token');
            $table->timestamp('session_created_at')->nullable()->after('active_session_token');
            
            // Advanced security tracking
            $table->string('device_fingerprint')->nullable()->after('session_created_at');
            $table->string('last_login_ip')->nullable()->after('device_fingerprint');
            $table->string('last_user_agent')->nullable()->after('last_login_ip');
            $table->timestamp('last_security_check')->nullable()->after('last_user_agent');
            $table->integer('concurrent_login_attempts')->default(0)->after('last_security_check');
            $table->timestamp('last_suspicious_activity')->nullable()->after('concurrent_login_attempts');
            
            // Performance indexes
            $table->index(['active_session_token']);
            $table->index(['last_login_ip']);
            $table->index(['last_security_check']);
        });

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
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['active_session_token']);
            $table->dropIndex(['last_login_ip']);
            $table->dropIndex(['last_security_check']);
            
            $table->dropColumn([
                'active_session_token',
                'session_created_at',
                'device_fingerprint',
                'last_login_ip',
                'last_user_agent',
                'last_security_check',
                'concurrent_login_attempts',
                'last_suspicious_activity'
            ]);
        });
    }
};