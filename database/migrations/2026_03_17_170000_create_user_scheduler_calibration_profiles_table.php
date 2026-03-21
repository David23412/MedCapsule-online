<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('user_scheduler_calibration_profiles')) {
            return;
        }

        Schema::create('user_scheduler_calibration_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('mode', 32);
            $table->unsignedInteger('total_reviews')->default(0);
            $table->unsignedInteger('calibration_reviews')->default(0);
            $table->decimal('confidence_sensitivity', 6, 4)->default(0.1800);
            $table->decimal('overconfidence_penalty', 6, 4)->default(0.2200);
            $table->decimal('confidence_reliability', 6, 4)->default(1.0000);
            $table->decimal('high_confidence_accuracy', 6, 4)->default(0.0000);
            $table->decimal('low_confidence_accuracy', 6, 4)->default(0.0000);
            $table->decimal('confidence_alignment', 6, 4)->default(0.0000);
            $table->timestamp('last_calibrated_at')->nullable();
            $table->json('calibration_meta')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'mode']);
            $table->index(['mode', 'last_calibrated_at'], 'usr_sched_cal_mode_last_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_scheduler_calibration_profiles');
    }
};
