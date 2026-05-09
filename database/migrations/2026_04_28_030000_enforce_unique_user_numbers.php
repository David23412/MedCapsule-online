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

        // If the unique constraint already exists, skip (idempotent).
        if (Schema::hasIndex('users', 'users_number_unique')) {
            return;
        }

        // Resolve duplicates before adding the unique constraint.
        $duplicateNumbers = DB::table('users')
            ->select('number', DB::raw('COUNT(*) as aggregate'))
            ->whereNotNull('number')
            ->where('number', '!=', '')
            ->groupBy('number')
            ->having('aggregate', '>', 1)
            ->pluck('number')
            ->all();

        foreach ($duplicateNumbers as $number) {
            // Keep the record with the lowest id unchanged; append "_dup_{id}" to the rest.
            $keepId = DB::table('users')
                ->where('number', $number)
                ->min('id');

            $duplicatesToFix = DB::table('users')
                ->where('number', $number)
                ->where('id', '!=', $keepId)
                ->pluck('id');

            foreach ($duplicatesToFix as $fixId) {
                DB::table('users')
                    ->where('id', $fixId)
                    ->update(['number' => $number . '_dup_' . $fixId]);
            }
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
