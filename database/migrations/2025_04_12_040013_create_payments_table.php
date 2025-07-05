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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('course_id')->constrained()->onDelete('cascade');
            $table->string('reference_number')->unique();
            $table->decimal('amount', 10, 2);
            $table->string('payment_method'); // 'fawry', 'vodafone_cash', etc.
            $table->string('status'); // 'pending', 'completed', 'failed', 'expired'
            $table->json('payment_data')->nullable(); // Store gateway-specific data
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            // Indexes for better performance
            $table->index('user_id');
            $table->index('course_id');
            $table->index(['user_id', 'course_id']);
            $table->index('reference_number');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
