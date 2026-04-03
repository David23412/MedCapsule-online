<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('user_mistake_mastery') || Schema::hasColumn('user_mistake_mastery', 'graduation_mode')) {
            return;
        }

        Schema::table('user_mistake_mastery', function (Blueprint $table) {
            $table->string('graduation_mode', 20)->nullable()->after('consecutive_correct');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('user_mistake_mastery') || !Schema::hasColumn('user_mistake_mastery', 'graduation_mode')) {
            return;
        }

        Schema::table('user_mistake_mastery', function (Blueprint $table) {
            $table->dropColumn('graduation_mode');
        });
    }
};
