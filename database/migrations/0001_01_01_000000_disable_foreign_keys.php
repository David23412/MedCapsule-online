<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * SECURITY FIX: Only disable foreign keys temporarily during migration
     */
    public function up(): void
    {
        // This migration is now a no-op for security
        // Foreign key constraints should be maintained for data integrity
        // If specific migrations need FK disabled, they should handle it individually
        
        // Log that this migration ran but did nothing
        \Log::info('Foreign key migration ran - no action taken for security');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Ensure foreign keys are enabled
        if (DB::getDriverName() === 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }
    }
}; 