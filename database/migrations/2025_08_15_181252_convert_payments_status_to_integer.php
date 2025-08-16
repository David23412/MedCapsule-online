<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Check if status column exists (it may have been removed in SSOT cleanup)
        if (!Schema::hasColumn('payments', 'status')) {
            // Add status column back temporarily for migration
            Schema::table('payments', function (Blueprint $table) {
                $table->string('status')->default('pending')->after('payment_data');
            });
            
            // Populate status from SSOT logic (paid_at timestamp)
            DB::statement("
                UPDATE payments SET status = CASE 
                    WHEN failed_at IS NOT NULL THEN 'failed'
                    WHEN paid_at IS NOT NULL THEN 'completed'
                    WHEN created_at < DATE_SUB(NOW(), INTERVAL 24 HOUR) THEN 'expired'
                    ELSE 'pending'
                END
            ");
        }
        
        // Step 1: Normalize existing string values to integers (as strings first)
        DB::statement("
            UPDATE payments SET status = CASE 
                WHEN LOWER(TRIM(status)) = 'pending' THEN '1'
                WHEN LOWER(TRIM(status)) = 'completed' THEN '2'
                WHEN LOWER(TRIM(status)) = 'failed' THEN '3'
                WHEN LOWER(TRIM(status)) = 'expired' THEN '4'
                ELSE '1'
            END
        ");
        
        // Step 2: Convert column type to integer in-place
        Schema::table('payments', function (Blueprint $table) {
            $table->tinyInteger('status')->unsigned()->default(1)->change();
            $table->index('status', 'idx_payments_status');
        });
        
        // Step 3: Add check constraint for data integrity
        DB::statement('ALTER TABLE payments ADD CONSTRAINT chk_payments_status CHECK (status IN (1, 2, 3, 4))');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Step 1: Remove check constraint
        DB::statement('ALTER TABLE payments DROP CONSTRAINT IF EXISTS chk_payments_status');
        
        // Step 2: Drop index
        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex('idx_payments_status');
        });
        
        // Step 3: Convert back to string values
        DB::statement("
            UPDATE payments SET status = CASE 
                WHEN status = 1 THEN 'pending'
                WHEN status = 2 THEN 'completed'
                WHEN status = 3 THEN 'failed'
                WHEN status = 4 THEN 'expired'
                ELSE 'pending'
            END
        ");
        
        // Step 4: Convert column type back to string
        Schema::table('payments', function (Blueprint $table) {
            $table->string('status')->default('pending')->change();
        });
    }
};