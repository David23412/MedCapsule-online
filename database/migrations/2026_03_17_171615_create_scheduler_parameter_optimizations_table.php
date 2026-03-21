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
        Schema::create('scheduler_parameter_optimizations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->string('mode', 20)->index(); // 'spaced', 'quiz', 'quick'
            $table->json('parameters'); // Optimized weight matrix
            $table->integer('sample_size'); // Number of reviews used for optimization
            $table->decimal('log_loss', 8, 4); // Optimization quality metric
            $table->timestamp('optimized_at')->index();
            $table->timestamps();
            
            $table->unique(['user_id', 'mode', 'optimized_at'], 'sched_param_opt_user_mode_opt_at_uq');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scheduler_parameter_optimizations');
    }
};
