<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('assignments') || Schema::hasColumn('assignments', 'submitted_at')) {
            return;
        }

        Schema::table('assignments', function (Blueprint $table) {
            $table->timestamp('submitted_at')->nullable()->after('deadline');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('assignments') || !Schema::hasColumn('assignments', 'submitted_at')) {
            return;
        }

        Schema::table('assignments', function (Blueprint $table) {
            $table->dropColumn('submitted_at');
        });
    }
};
