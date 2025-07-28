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
        Schema::table('payments', function (Blueprint $table) {
            // Add only new indexes that don't exist in the original migration
            $table->index(['status', 'created_at']);
            $table->index(['status', 'created_at', 'user_id']);
            $table->index(['status', 'payment_method']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('payments')) {
            Schema::table('payments', function (Blueprint $table) {
                // Drop only the indexes we added
                try {
                    $table->dropIndex(['status', 'created_at']);
                } catch (\Exception $e) {
                    // Index might not exist
                }
                try {
                    $table->dropIndex(['status', 'created_at', 'user_id']);
                } catch (\Exception $e) {
                    // Index might not exist
                }
                try {
                    $table->dropIndex(['status', 'payment_method']);
                } catch (\Exception $e) {
                    // Index might not exist
                }
            });
        }
    }
};
