<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('users')) {
            Schema::create('users', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('email')->unique();
                $table->timestamp('email_verified_at')->nullable();
                $table->string('password');
                $table->boolean('is_admin')->default(false);
                $table->rememberToken();
                $table->string('university')->nullable();
                $table->string('profile_picture_url')->nullable();
                $table->timestamp('last_active_at')->nullable();
                $table->integer('correct_answers_count')->default(0);
                $table->integer('total_questions_attempted')->default(0);
                $table->integer('xp')->default(0);
                $table->integer('study_streak_days')->default(0);
                $table->unsignedBigInteger('study_time_seconds')->default(0);
                $table->timestamp('last_study_date')->nullable();
                $table->timestamps();
            });
        }

        // Create Password Reset Tokens table if it doesn't exist
        if (!Schema::hasTable('password_reset_tokens')) {
            Schema::create('password_reset_tokens', function (Blueprint $table) {
                $table->string('email')->primary();
                $table->string('token');
                $table->timestamp('created_at')->nullable();
            });
        }

        // Create Sessions Table if it doesn't exist
        if (!Schema::hasTable('sessions')) {
            Schema::create('sessions', function (Blueprint $table) {
                $table->string('id')->primary();
                $table->foreignId('user_id')->nullable()->index();
                $table->string('ip_address', 45)->nullable();
                $table->text('user_agent')->nullable();
                $table->longText('payload');
                $table->integer('last_activity')->index();
                $table->boolean('is_visitor')->default(true);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
    }
};