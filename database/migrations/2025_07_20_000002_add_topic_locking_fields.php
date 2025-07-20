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
        Schema::table('topics', function (Blueprint $table) {
            // Add missing locking fields if they don't exist
            if (!Schema::hasColumn('topics', 'lock_reason')) {
                $table->text('lock_reason')->nullable()->after('locked_by_tutor_id');
            }
            
            if (!Schema::hasColumn('topics', 'locked_at')) {
                $table->timestamp('locked_at')->nullable()->after('lock_reason');
            }
            
            if (!Schema::hasColumn('topics', 'unlocked_at')) {
                $table->timestamp('unlocked_at')->nullable()->after('locked_at');
            }

            // Add indexes for the new fields
            $table->index(['locked_at', 'locked_by_tutor_id'], 'idx_topics_locked_at_tutor');
            $table->index('unlocked_at', 'idx_topics_unlocked_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('topics', function (Blueprint $table) {
            $table->dropIndex('idx_topics_locked_at_tutor');
            $table->dropIndex('idx_topics_unlocked_at');
            
            $table->dropColumn(['lock_reason', 'locked_at', 'unlocked_at']);
        });
    }
};