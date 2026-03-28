<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('users')) {
            return;
        }

        if (Schema::hasColumn('users', 'target_score')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('target_score');
            });
        }

        if (Schema::hasColumn('users', 'number')) {
            DB::table('users')
                ->whereNull('number')
                ->orWhere('number', '')
                ->update([
                    'number' => DB::raw("CONCAT('missing-', id)"),
                ]);

            DB::statement('ALTER TABLE users MODIFY number VARCHAR(20) NOT NULL');
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('users')) {
            return;
        }

        if (!Schema::hasColumn('users', 'target_score')) {
            Schema::table('users', function (Blueprint $table) {
                $table->unsignedTinyInteger('target_score')->default(80)->after('exam_date');
            });
        }

        if (Schema::hasColumn('users', 'number')) {
            DB::statement('ALTER TABLE users MODIFY number VARCHAR(20) NULL');
        }
    }
};
