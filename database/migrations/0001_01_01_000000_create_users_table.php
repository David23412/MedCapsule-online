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
                $table->string('number', 20)->nullable();
                $table->timestamp('email_verified_at')->nullable();
                $table->string('password');
                $table->unsignedTinyInteger('role')->default(3); // 1=admin,2=tutor,3=student (SSOT)
                $table->integer('weekly_goal')->default(50);
                $table->date('exam_date')->nullable();
                $table->unsignedTinyInteger('target_score')->default(80);
                $table->unsignedSmallInteger('daily_study_minutes')->default(90);
                $table->string('planner_focus')->default('balanced');
                $table->string('repair_mode', 20)->nullable();
                $table->string('mistake_mastery_mode', 20)->nullable();
                $table->boolean('has_sr_access')->default(false);
                $table->rememberToken();
                $table->string('active_session_token')->nullable();
                $table->timestamp('session_created_at')->nullable();
                $table->string('device_fingerprint')->nullable();
                $table->string('last_login_ip')->nullable();
                $table->string('last_user_agent')->nullable();
                $table->timestamp('last_security_check')->nullable();
                $table->integer('concurrent_login_attempts')->default(0);
                $table->timestamp('last_suspicious_activity')->nullable();
                $table->string('university')->nullable();
                $table->string('profile_picture_url')->nullable();
                $table->timestamp('last_active_at')->nullable();
                $table->integer('correct_answers_count')->default(0);
                $table->integer('total_questions_attempted')->default(0);
                $table->integer('xp')->default(0);
                $table->integer('level')->default(1);
                $table->integer('study_streak_days')->default(0);
                $table->unsignedBigInteger('study_time_seconds')->default(0);
                $table->boolean('survey_completed')->default(false);
                $table->boolean('free_courses_unlocked')->default(false);
                $table->timestamp('survey_completed_at')->nullable();
                $table->timestamp('free_courses_unlocked_at')->nullable();
                $table->timestamp('last_study_date')->nullable();
                $table->timestamps();

                $table->index('role');
                $table->index('xp');
                $table->index('study_streak_days');
                $table->index('name');
                $table->index('number');
                $table->index('last_active_at');
                $table->index('created_at');
                $table->index('active_session_token');
                $table->index('last_login_ip');
                $table->index('last_security_check');
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
                $table->string('fingerprint')->nullable(); // Session fingerprint for security
                $table->longText('payload');
                $table->integer('last_activity')->index();
                $table->boolean('is_visitor')->default(true);
            });
        }
    }

    public function down(): void
    {
        // ✅ FIXED: Remove Schema::disableForeignKeyConstraints() - maintain data integrity
        // Drop dependent pivot tables that may reference users
        if (Schema::hasTable('assignment_users')) {
            Schema::drop('assignment_users');
        }
        if (Schema::hasTable('assignment_user')) {
            Schema::drop('assignment_user');
        }
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
        // ✅ FIXED: Remove Schema::enableForeignKeyConstraints() - not needed when not disabled
    }
};
