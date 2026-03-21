<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('scheduler_calibration_profiles')) {
            return;
        }

        Schema::create('scheduler_calibration_profiles', function (Blueprint $table) {
            $table->id();
            $table->string('mode')->unique();
            $table->unsignedInteger('total_reviews')->default(0);
            $table->unsignedInteger('calibration_reviews')->default(0);
            $table->decimal('observed_recall_rate', 5, 4)->default(0.0000);
            $table->decimal('mean_prediction_error', 6, 4)->default(0.0000);
            $table->decimal('recall_bias', 5, 4)->default(0.0000);
            $table->decimal('stability_scale', 6, 4)->default(1.0000);
            $table->decimal('difficulty_scale', 6, 4)->default(1.0000);
            $table->decimal('response_sensitivity', 6, 4)->default(0.1200);
            $table->decimal('confidence_sensitivity', 6, 4)->default(0.1600);
            $table->decimal('overconfidence_penalty', 6, 4)->default(0.1800);
            $table->decimal('hesitation_sensitivity', 6, 4)->default(0.1200);
            $table->decimal('relearn_scale', 6, 4)->default(1.0000);
            $table->decimal('decay_base', 6, 4)->default(0.9000);
            $table->timestamp('last_calibrated_at')->nullable();
            $table->json('calibration_meta')->nullable();
            $table->timestamps();

            $table->index(['mode', 'last_calibrated_at'], 'sched_cal_mode_last_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scheduler_calibration_profiles');
    }
};
