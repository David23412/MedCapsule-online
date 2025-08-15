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
            // SSOT: No status column - derive from timestamps and business logic
            $table->json('payment_data')->nullable(); // Store gateway-specific data
            $table->timestamp('paid_at')->nullable()->comment('SSOT: NULL = not paid, timestamp = payment completed');
            $table->timestamp('failed_at')->nullable()->comment('When payment failed (if applicable)');
            
            $table->timestamps();

            // 🚀 LEAN INDEXES using SSOT fields
            $table->index(['user_id', 'paid_at']); // For user payment history
            $table->index(['course_id', 'paid_at']); // For course revenue queries
            $table->index('reference_number');
            $table->index('paid_at'); // For completed payments queries
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
