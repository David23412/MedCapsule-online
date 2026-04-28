<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('users') || !Schema::hasColumn('users', 'number')) {
            return;
        }

        $duplicates = DB::table('users')
            ->select('number', DB::raw('COUNT(*) as aggregate'))
            ->whereNotNull('number')
            ->where('number', '!=', '')
            ->groupBy('number')
            ->having('aggregate', '>', 1)
            ->limit(10)
            ->pluck('number')
            ->all();

        if ($duplicates !== []) {
            throw new RuntimeException(
                'Cannot add unique constraint to users.number until duplicates are resolved. Duplicate numbers: '.implode(', ', $duplicates)
            );
        }

        Schema::table('users', function (Blueprint $table) {
            $table->unique('number', 'users_number_unique');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('users')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique('users_number_unique');
        });
    }
};
